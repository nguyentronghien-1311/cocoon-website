<?php
/** Secure checkout endpoint. Creates an order and its items atomically. */
require_once __DIR__ . '/../cart/cart_common.php';
require_once __DIR__ . '/../../includes/coupon_common.php';

function cocoon_order_redirect_error(string $message, int $status = 422, array $input = []): never
{
    $_SESSION['checkout_flash_error'] = $message;
    $_SESSION['checkout_old_input'] = [
        'customer_name' => trim((string) ($input['customer_name'] ?? '')),
        'customer_phone' => trim((string) ($input['customer_phone'] ?? '')),
        'customer_address' => trim((string) ($input['customer_address'] ?? '')),
        'address_id' => (string) ($input['address_id'] ?? ''),
        'payment_method' => (string) ($input['payment_method'] ?? 'cod'),
        'note' => trim((string) ($input['note'] ?? '')),
        'coupon_code' => strtoupper(trim((string) ($input['coupon_code'] ?? ''))),
    ];
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    if (str_contains($accept, 'application/json') || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
        cocoon_cart_json(['success' => false, 'message' => $message], $status);
    }
    header('Location: ../../checkout.php');
    exit;
}

function cocoon_order_text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    header('Allow: POST');
    cocoon_cart_json(['success' => false, 'message' => 'Chỉ chấp nhận yêu cầu POST.'], 405);
}

$data = cocoon_cart_request_data();
$csrf = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? ''));
if ($csrf === '' || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    cocoon_order_redirect_error('Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.', 419, $data);
}
$checkoutToken = (string) ($data['checkout_token'] ?? '');
if ($checkoutToken === '' || empty($_SESSION['checkout_token']) || !hash_equals((string) $_SESSION['checkout_token'], $checkoutToken)) {
    cocoon_order_redirect_error('Yêu cầu đặt hàng đã được gửi hoặc đã hết hạn. Vui lòng kiểm tra giỏ hàng và thử lại.', 409, $data);
}

$cart = $_SESSION['cart'] ?? [];
if (!is_array($cart) || $cart === []) {
    cocoon_order_redirect_error('Giỏ hàng đang trống. Vui lòng thêm sản phẩm trước khi đặt hàng.', 422, $data);
}
$cleanCart = [];
foreach ($cart as $id => $quantity) {
    $productId = cocoon_cart_parse_positive_int((string) $id);
    $qty = cocoon_cart_parse_positive_int($quantity);
    if ($productId === null || $qty === null || $qty > 999) {
        cocoon_order_redirect_error('Dữ liệu giỏ hàng không hợp lệ. Vui lòng cập nhật giỏ hàng.', 422, $data);
    }
    $cleanCart[$productId] = $qty;
}

$note = trim((string) ($data['note'] ?? ''));
$paymentMethod = (string) ($data['payment_method'] ?? 'cod');
if (cocoon_order_text_length($note) > 255) {
    cocoon_order_redirect_error('Ghi chú không được vượt quá 255 ký tự.', 422, $data);
}
if (!in_array($paymentMethod, ['cod', 'banking'], true)) {
    cocoon_order_redirect_error('Vui lòng chọn phương thức thanh toán hợp lệ.', 422, $data);
}

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$couponCode = strtoupper(trim((string) ($data['coupon_code'] ?? '')));
if ($couponCode !== '' && $userId === null) {
    cocoon_order_redirect_error('Chỉ tài khoản đã đăng nhập mới có thể áp dụng mã giảm giá.', 403, $data);
}
$customerName = '';
$customerPhone = '';
$customerAddress = '';

try {
    $pdo = cocoon_pdo();

    if ($userId !== null) {
        $addressId = cocoon_cart_parse_positive_int($data['address_id'] ?? null);
        if ($addressId === null) {
            cocoon_order_redirect_error('Vui lòng chọn địa chỉ giao hàng đã lưu.', 422, $data);
        }
        $addressQuery = $pdo->prepare(
            'SELECT recipient_name, phone, address FROM user_addresses WHERE id = :address_id AND user_id = :user_id LIMIT 1'
        );
        $addressQuery->execute([':address_id' => $addressId, ':user_id' => $userId]);
        $delivery = $addressQuery->fetch();
        if (!$delivery) {
            cocoon_order_redirect_error('Địa chỉ giao hàng không hợp lệ hoặc không thuộc tài khoản của bạn.', 403, $data);
        }
        $customerName = trim((string) $delivery['recipient_name']);
        $customerPhone = trim((string) $delivery['phone']);
        $customerAddress = trim((string) $delivery['address']);
    } else {
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $customerPhone = trim((string) ($data['customer_phone'] ?? ''));
        $customerAddress = trim((string) ($data['customer_address'] ?? ''));
        if (
            $customerName === '' || cocoon_order_text_length($customerName) > 100 ||
            $customerPhone === '' || cocoon_order_text_length($customerPhone) > 20 ||
            !preg_match('/^[0-9+().\s-]{7,20}$/u', $customerPhone) ||
            $customerAddress === '' || cocoon_order_text_length($customerAddress) > 255
        ) {
            cocoon_order_redirect_error('Vui lòng nhập họ tên, số điện thoại và địa chỉ giao hàng hợp lệ.', 422, $data);
        }
    }

    if ($customerName === '' || cocoon_order_text_length($customerName) > 100 || $customerPhone === '' || cocoon_order_text_length($customerPhone) > 20 || $customerAddress === '' || cocoon_order_text_length($customerAddress) > 255) {
        cocoon_order_redirect_error('Thông tin giao hàng không hợp lệ. Vui lòng kiểm tra lại.', 422, $data);
    }

    // Sort product IDs to keep row-lock order deterministic and reduce deadlock risk.
    $productIds = array_keys($cleanCart);
    sort($productIds, SORT_NUMERIC);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $pdo->beginTransaction();
    $lockProducts = $pdo->prepare(
        "SELECT id, name, price, stock FROM products WHERE id IN ($placeholders) ORDER BY id FOR UPDATE"
    );
    $lockProducts->execute($productIds);
    $products = [];
    foreach ($lockProducts->fetchAll() as $product) {
        $products[(int) $product['id']] = $product;
    }

    if (count($products) !== count($cleanCart)) {
        throw new DomainException('Một hoặc nhiều sản phẩm trong giỏ không còn tồn tại. Vui lòng cập nhật giỏ hàng.');
    }

    $orderItems = [];
    $totalAmount = 0;
    foreach ($cleanCart as $productId => $quantity) {
        $product = $products[$productId];
        if ((int) $product['stock'] < $quantity) {
            throw new DomainException('Sản phẩm "' . (string) $product['name'] . '" không đủ tồn kho cho số lượng đã chọn. Vui lòng cập nhật giỏ hàng.');
        }
        $price = (int) $product['price'];
        $lineTotal = $price * $quantity;
        $totalAmount += $lineTotal;
        $orderItems[] = [
            'product_id' => (int) $productId,
            'product_name' => (string) $product['name'],
            'price' => $price,
            'quantity' => $quantity,
        ];
    }

    if ($couponCode !== '') {
        $totalAmount -= cocoon_coupon_discount($couponCode, $totalAmount);
    }

    $orderInsert = $pdo->prepare(
        "INSERT INTO orders (customer_name, customer_phone, customer_address, total_amount, status, payment_method, user_id, note)
         VALUES (:name, :phone, :address, :total, 'Chờ duyệt', :payment, :user_id, :note)"
    );
    $orderInsert->execute([
        ':name' => $customerName,
        ':phone' => $customerPhone,
        ':address' => $customerAddress,
        ':total' => $totalAmount,
        ':payment' => $paymentMethod === 'banking' ? 'Chuyển khoản' : 'COD',
        ':user_id' => $userId,
        ':note' => $note !== '' ? $note : null,
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $itemInsert = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (:order_id, :product_id, :product_name, :price, :quantity)'
    );
    $stockUpdate = $pdo->prepare(
        'UPDATE products SET stock = stock - :quantity_to_subtract WHERE id = :product_id AND stock >= :quantity_to_check'
    );

    foreach ($orderItems as $item) {
        $itemInsert->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['product_id'],
            ':product_name' => $item['product_name'],
            ':price' => $item['price'],
            ':quantity' => $item['quantity'],
        ]);
        $stockUpdate->execute([
            ':quantity_to_subtract' => $item['quantity'],
            ':product_id' => $item['product_id'],
            ':quantity_to_check' => $item['quantity'],
        ]);
        if ($stockUpdate->rowCount() !== 1) {
            throw new DomainException('Tồn kho vừa thay đổi. Vui lòng kiểm tra lại giỏ hàng và đặt hàng lần nữa.');
        }
    }

    $pdo->commit();

    // Remove cart only after a successful commit. Rotate the form token to block reposts.
    $_SESSION['cart'] = [];
    $_SESSION['last_order_id'] = $orderId;
    if ($couponCode !== '') {
        $_SESSION['last_order_coupon'] = ['order_id' => $orderId, 'code' => $couponCode];
    } else {
        unset($_SESSION['last_order_coupon']);
    }
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
    unset($_SESSION['checkout_old_input'], $_SESSION['checkout_flash_error']);

    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    if (str_contains($accept, 'application/json') || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
        cocoon_cart_json(['success' => true, 'message' => 'Đặt hàng thành công.', 'data' => ['order_id' => $orderId, 'total_amount' => $totalAmount]], 201);
    }
    header('Location: ../../checkout.php?order=' . $orderId);
    exit;
} catch (DomainException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    cocoon_order_redirect_error($exception->getMessage(), 409, $data);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Cocoon orders_create] ' . $exception->getMessage());
    cocoon_order_redirect_error('Không thể xử lý đơn hàng lúc này. Dữ liệu chưa được đặt hàng; vui lòng thử lại sau.', 500, $data);
}

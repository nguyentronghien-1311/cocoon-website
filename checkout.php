<?php
require_once __DIR__ . '/backend/cart/cart_common.php';

if (empty($_SESSION['checkout_token'])) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
}

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;
$error = trim((string) ($_SESSION['checkout_flash_error'] ?? ''));
unset($_SESSION['checkout_flash_error']);
$oldInput = is_array($_SESSION['checkout_old_input'] ?? null) ? $_SESSION['checkout_old_input'] : [];
unset($_SESSION['checkout_old_input']);
$note = trim((string) ($oldInput['note'] ?? ''));
$customerName = trim((string) ($oldInput['customer_name'] ?? ''));
$customerPhone = trim((string) ($oldInput['customer_phone'] ?? ''));
$customerAddress = trim((string) ($oldInput['customer_address'] ?? ''));
$selectedAddressId = (int) ($oldInput['address_id'] ?? 0);
$addresses = [];
$confirmedOrder = null;
$successOrderId = 0;
$orderItems = [];
$totalAmount = 0;
$cartEmpty = false;
$formatCurrency = static fn ($amount) => number_format((int) $amount, 0, ',', '.') . ' đ';
$pdo = null;

try {
    $pdo = cocoon_pdo();

    if ($isLoggedIn) {
        // Preserve the existing convenience of seeding a saved address from the profile.
        $profileQuery = $pdo->prepare('SELECT full_name, phone, address FROM users WHERE id = :user_id LIMIT 1');
        $profileQuery->execute([':user_id' => $userId]);
        $profile = $profileQuery->fetch();
        if ($profile && trim((string) ($profile['address'] ?? '')) !== '' && trim((string) ($profile['phone'] ?? '')) !== '') {
            $countQuery = $pdo->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id');
            $countQuery->execute([':user_id' => $userId]);
            if ((int) $countQuery->fetchColumn() === 0) {
                $seedAddress = $pdo->prepare(
                    'INSERT INTO user_addresses (user_id, label, recipient_name, phone, address, is_default) VALUES (:user_id, :label, :recipient_name, :phone, :address, 1)'
                );
                $seedAddress->execute([
                    ':user_id' => $userId,
                    ':label' => 'Nhà riêng',
                    ':recipient_name' => $profile['full_name'],
                    ':phone' => $profile['phone'],
                    ':address' => $profile['address'],
                ]);
            }
        }
        $addressQuery = $pdo->prepare('SELECT id, label, recipient_name, phone, address, is_default FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, id DESC');
        $addressQuery->execute([':user_id' => $userId]);
        $addresses = $addressQuery->fetchAll();
        $selectedAddressIsValid = false;
        foreach ($addresses as $savedAddress) {
            if ($selectedAddressId === (int) $savedAddress['id']) {
                $selectedAddressIsValid = true;
                break;
            }
        }
        if (!$selectedAddressIsValid) {
            $selectedAddressId = 0;
            foreach ($addresses as $savedAddress) {
                if ((int) $savedAddress['is_default'] === 1) {
                    $selectedAddressId = (int) $savedAddress['id'];
                    break;
                }
            }
            if ($selectedAddressId === 0 && $addresses) {
                $selectedAddressId = (int) $addresses[0]['id'];
            }
        }
    }

    // A confirmation is visible only to the PHP session that just placed that order.
    $requestedOrderId = (int) ($_GET['order'] ?? 0);
    $lastOrderId = (int) ($_SESSION['last_order_id'] ?? 0);
    if ($requestedOrderId > 0 && $requestedOrderId === $lastOrderId) {
        $orderQuery = $pdo->prepare('SELECT id, customer_name, customer_phone, customer_address, total_amount, payment_method, status, note, created_at FROM orders WHERE id = :id LIMIT 1');
        $orderQuery->execute([':id' => $requestedOrderId]);
        $confirmedOrder = $orderQuery->fetch() ?: null;
        if ($confirmedOrder) {
            $successOrderId = (int) $confirmedOrder['id'];
            $itemsQuery = $pdo->prepare('SELECT product_id, product_name, price, quantity FROM order_items WHERE order_id = :order_id ORDER BY id');
            $itemsQuery->execute([':order_id' => $successOrderId]);
            foreach ($itemsQuery->fetchAll() as $item) {
                $orderItems[] = [
                    'id' => (int) ($item['product_id'] ?? 0),
                    'name' => (string) $item['product_name'],
                    'price' => (int) $item['price'],
                    'quantity' => (int) $item['quantity'],
                    'line_total' => (int) $item['price'] * (int) $item['quantity'],
                ];
            }
            $totalAmount = (int) $confirmedOrder['total_amount'];
        }
    }

    if (!$confirmedOrder) {
        $snapshot = cocoon_cart_snapshot($pdo);
        foreach ($snapshot['items'] as $item) {
            $orderItems[] = [
                'id' => (int) $item['id'],
                'name' => (string) $item['name'],
                'price' => (int) $item['price'],
                'quantity' => (int) $item['quantity'],
                'line_total' => (int) $item['line_total'],
            ];
        }
        $totalAmount = (int) $snapshot['subtotal'];
    }
    $cartEmpty = !$confirmedOrder && count($orderItems) === 0;
} catch (Throwable $exception) {
    error_log('[Cocoon checkout page] ' . $exception->getMessage());
    if ($error === '') {
        $error = 'Không thể tải thông tin thanh toán. Vui lòng kiểm tra kết nối cơ sở dữ liệu và thử lại.';
    }
    $cartEmpty = !$confirmedOrder && count($orderItems) === 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - Mỹ Phẩm Thuần Chay Cocoon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2E7D32;    /* Màu xanh lá đậm Cocoon */
            --secondary: #8D6E63;  /* Nâu mộc */
            --bg: #F9F9F6;         /* Nền be tự nhiên */
            --text: #2c3e50;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 0;
        }
        header {
            position: relative;
            z-index: 10;
            background: white;
            border-bottom: 2px solid #e0e0e0;
            padding: 15px 40px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 20px;
        }
        .logo {
            grid-column: 2;
            grid-row: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-weight: 500;
            color: #3E3228;
            text-decoration: none;
        }
        .logo-the, .logo-name { font-family: "Cormorant Garamond", Georgia, serif; font-style: italic; }
        .logo-the { margin-bottom: -4px; font-size: 13px; line-height: 1; }
        .logo-name { font-size: 28px; line-height: 0.9; }
        .logo-country { margin-top: 4px; color: #8D6E63; font-size: 8px; letter-spacing: 0.38em; }
        .site-menu { position: relative; grid-column: 3; grid-row: 1; justify-self: end; }
        .tagline { grid-column: 1; grid-row: 1; }
        .menu-toggle {
            width: 42px;
            height: 42px;
            display: grid;
            align-content: center;
            justify-items: center;
            gap: 5px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: white;
            cursor: pointer;
        }
        .menu-toggle span { width: 20px; height: 2px; background: var(--primary); }
        .menu-panel {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 190px;
            padding: 6px;
            border: 1px solid #e0e0e0;
            background: white;
            box-shadow: 0 8px 22px rgba(0,0,0,.12);
        }
        .menu-panel[hidden] { display: none; }
        .menu-panel a { display: block; padding: 10px 12px; color: var(--text); text-decoration: none; }
        .menu-panel a:hover { background: var(--bg); color: var(--primary); }
        .menu-panel form { margin: 0; }
        .menu-panel button { width: 100%; padding: 10px 12px; border: 0; background: transparent; color: var(--text); font: inherit; text-align: left; cursor: pointer; }
        .menu-panel button:hover { background: var(--bg); color: var(--primary); }
        @media (max-width: 600px) {
            header { padding: 14px 18px; }
            .tagline { display: none; }
        }
        .container {
            max-width: 1000px;
            margin: 35px auto;
            display: flex;
            gap: 30px;
        }
        /* Cột bên trái: Form điền thông tin */
        .checkout-form {
            flex: 1.6;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        /* Cột bên phải: Tóm tắt đơn hàng */
        .order-summary {
            flex: 1.1;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            height: fit-content;
        }
        h2 {
            font-size: 20px;
            color: var(--primary);
            margin-top: 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            font-size: 14px;
        }
        input[type="text"], input[type="tel"], textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #fff;
            font-size: 14px;
        }
        input:focus, textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        select:focus { border-color: var(--primary); outline: none; }
        .notice { margin-bottom: 18px; padding: 12px 14px; border-radius: 6px; background: #edf5ed; color: #256629; }
        .error { margin-bottom: 18px; padding: 12px 14px; border-radius: 6px; background: #fff1f1; color: #b71c1c; }
        .manage-address { display: inline-block; margin-top: 10px; color: var(--primary); font-size: 14px; }
        .manage-address:hover { text-decoration: underline; }
        .payment-methods {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .payment-option {
            border: 1px solid #ddd;
            padding: 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .payment-option:hover {
            border-color: var(--primary);
            background-color: #f6fbf6;
        }
        /* Danh sách tóm tắt hàng bên phải */
        .item-list {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 10px;
        }
        .price-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .total-price {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary);
            border-top: 1px solid #eee;
            padding-top: 12px;
        }
        .btn-order {
            width: 100%;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn-order:hover {
            background-color: #1b5e20;
        }
        .btn-order:disabled { background: #9eaaa0; cursor: not-allowed; }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: var(--secondary);
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo" aria-label="the cocoon Vietnam - Trang chủ">
            <span class="logo-the">the</span>
            <span class="logo-name">cocoon</span>
            <span class="logo-country">VIETNAM</span>
        </a>
        <span class="tagline">Mỹ phẩm thuần chay 100% Việt Nam</span>
        <div class="site-menu">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Mở menu" aria-controls="menuPanel" aria-expanded="false"><span></span><span></span><span></span></button>
            <nav class="menu-panel" id="menuPanel" aria-label="Menu chính" hidden>
                <a href="index.php">Trang chủ</a>
                <a href="products.php">Sản phẩm</a>
                <a href="cart.php">Giỏ hàng</a>
                <a href="saved-coupons.php">Mã giảm giá đã lưu</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="addresses.php">Địa chỉ giao hàng</a>
                    <form action="logout.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Đăng xuất</button>
                    </form>
                <?php else: ?>
                    <a href="login.php">Đăng nhập</a>
                    <a href="register.php">Đăng ký</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Cột trái: Thông tin nhận hàng -->
        <div class="checkout-form">
            <?php if ($confirmedOrder): ?>
                <h2>Đặt Hàng Thành Công</h2>
                <div class="notice" role="status">Cảm ơn bạn đã mua sắm tại Cocoon. Mã đơn hàng của bạn là <strong>#<?= (int) $successOrderId ?></strong>.</div>
                <p><strong>Người nhận:</strong> <?= htmlspecialchars($confirmedOrder['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($confirmedOrder['customer_phone'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($confirmedOrder['customer_address'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Thanh toán:</strong> <?= htmlspecialchars($confirmedOrder['payment_method'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Trạng thái:</strong> <?= htmlspecialchars($confirmedOrder['status'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Tổng tiền:</strong> <?= $formatCurrency($confirmedOrder['total_amount']) ?></p>
                <?php if (!empty($confirmedOrder['note'])): ?>
                    <p><strong>Ghi chú:</strong> <?= htmlspecialchars($confirmedOrder['note'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <a class="btn-order" href="products.php" style="display:block;text-align:center;text-decoration:none;margin-top:20px;">TIẾP TỤC MUA SẮM</a>
            <?php else: ?>
                <h2>Thông Tin Giao Hàng</h2>
                <?php if ($error !== ''): ?>
                    <div class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($cartEmpty): ?>
                    <div class="error" role="alert">Giỏ hàng đang trống. Vui lòng thêm sản phẩm trước khi đặt hàng.</div>
                    <a class="back-link" href="products.php">← Tiếp tục mua sắm</a>
                <?php endif; ?>
                <form action="backend/orders/orders_create.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="checkout_token" value="<?= htmlspecialchars($_SESSION['checkout_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($isLoggedIn): ?>
                    <div class="form-group">
                        <label for="address_id">Địa chỉ giao hàng</label>
                        <?php if ($addresses): ?>
                            <select id="address_id" name="address_id" required>
                                <?php foreach ($addresses as $savedAddress): ?>
                                    <option value="<?= (int) $savedAddress['id'] ?>" <?= (int) $savedAddress['id'] === $selectedAddressId ? 'selected' : '' ?>><?= htmlspecialchars($savedAddress['label'] . ' · ' . $savedAddress['recipient_name'] . ' · ' . $savedAddress['phone'] . ' · ' . $savedAddress['address'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <p>Vui lòng thêm địa chỉ giao hàng trước khi thanh toán.</p>
                        <?php endif; ?>
                        <a class="manage-address" href="addresses.php">Quản lý địa chỉ giao hàng</a>
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <label for="customer_name">Họ và tên người nhận (*):</label>
                        <input type="text" id="customer_name" name="customer_name" maxlength="100" required value="<?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ví dụ: Nguyễn Văn A">
                    </div>
                    <div class="form-group">
                        <label for="customer_phone">Số điện thoại (*):</label>
                        <input type="tel" id="customer_phone" name="customer_phone" maxlength="20" required value="<?= htmlspecialchars($customerPhone, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ví dụ: 0987654321">
                    </div>
                    <div class="form-group">
                        <label for="customer_address">Địa chỉ nhận hàng chi tiết (*):</label>
                        <textarea id="customer_address" name="customer_address" maxlength="255" rows="3" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"><?= htmlspecialchars($customerAddress, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="note">Ghi chú đơn hàng (Tùy chọn):</label>
                        <textarea id="note" name="note" rows="2" maxlength="255" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao..."><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <h2>Phương Thức Thanh Toán</h2>
                    <div class="payment-methods">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod" <?= (($oldInput['payment_method'] ?? 'cod') === 'cod') ? 'checked' : '' ?>>
                            <span><strong>Thanh toán khi nhận hàng (COD)</strong> - Nhận hàng rồi mới trả tiền</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="banking" <?= (($oldInput['payment_method'] ?? '') === 'banking') ? 'checked' : '' ?>>
                            <span><strong>Chuyển khoản ngân hàng qua mã QR</strong></span>
                        </label>
                    </div>

                    <button type="submit" class="btn-order" <?= ($cartEmpty || ($isLoggedIn && !$addresses)) ? 'disabled' : '' ?>>XÁC NHẬN ĐẶT HÀNG</button>
                </form>
                <a href="cart.php" class="back-link">← Quay lại giỏ hàng kiểm tra</a>
            <?php endif; ?>
        </div>

        <!-- Cột phải: Xem lại giỏ hàng -->
        <div class="order-summary">
            <h2>Đơn Hàng Của Bạn</h2>
            <ul class="item-list">
                <?php foreach ($orderItems as $item): ?>
                <li class="item">
                    <span><?= (int) $item['quantity'] ?>x <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= $formatCurrency($item['line_total']) ?></strong>
                </li>
                <?php endforeach; ?>
                <?php if (!$orderItems): ?>
                    <li class="item"><span>Chưa có sản phẩm trong giỏ hàng.</span><strong>0 đ</strong></li>
                <?php endif; ?>
            </ul>

            <div class="price-line">
                <span>Tiền hàng:</span>
                <span><?= $formatCurrency($totalAmount) ?></span>
            </div>
            <div class="price-line">
                <span>Phí vận chuyển:</span>
                <span>0 đ (Miễn phí)</span>
            </div>
            <div class="price-line total-price">
                <span>Tổng cộng:</span>
                <span><?= $formatCurrency($totalAmount) ?></span>
            </div>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const menuPanel = document.getElementById('menuPanel');

        function closeMenu() {
            menuPanel.hidden = true;
            menuToggle.setAttribute('aria-expanded', 'false');
        }

        menuToggle.addEventListener('click', () => {
            const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
            menuPanel.hidden = isOpen;
            menuToggle.setAttribute('aria-expanded', String(!isOpen));
        });
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.site-menu')) closeMenu();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeMenu();
        });
    </script>
</body>
</html>
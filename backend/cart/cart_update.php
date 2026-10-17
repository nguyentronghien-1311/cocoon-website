<?php
require_once __DIR__ . '/cart_common.php';
cocoon_cart_require_post();
$data = cocoon_cart_request_data();
cocoon_cart_require_csrf($data);
$productId = cocoon_cart_parse_positive_int($data['product_id'] ?? null);
$quantity = cocoon_cart_parse_positive_int($data['quantity'] ?? null);
if ($productId === null || $quantity === null || $quantity > 999) {
    cocoon_cart_json(['success' => false, 'message' => 'Số lượng phải là số nguyên dương hợp lệ.'], 422);
}
if (!isset($_SESSION['cart'][$productId])) {
    cocoon_cart_json(['success' => false, 'message' => 'Sản phẩm không còn trong giỏ hàng.'], 404);
}
try {
    $pdo = cocoon_pdo();
    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
    if (!$product) {
        unset($_SESSION['cart'][$productId]);
        cocoon_cart_json(['success' => false, 'message' => 'Sản phẩm này không còn tồn tại.'], 404);
    }
    if ($quantity > (int) $product['stock']) {
        cocoon_cart_json(['success' => false, 'message' => 'Số lượng vượt quá tồn kho hiện tại.'], 409);
    }
    $_SESSION['cart'][$productId] = $quantity;
    cocoon_cart_json(['success' => true, 'message' => 'Đã cập nhật số lượng.', 'data' => cocoon_cart_snapshot($pdo)]);
} catch (Throwable $exception) {
    error_log('[Cocoon cart_update] ' . $exception->getMessage());
    cocoon_cart_json(['success' => false, 'message' => 'Không thể cập nhật giỏ hàng.'], 500);
}

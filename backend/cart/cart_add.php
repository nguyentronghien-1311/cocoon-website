<?php
require_once __DIR__ . '/cart_common.php';
cocoon_cart_require_post();
$data = cocoon_cart_request_data();
cocoon_cart_require_csrf($data);
$productId = cocoon_cart_parse_positive_int($data['product_id'] ?? null);
$quantity = array_key_exists('quantity', $data) ? cocoon_cart_parse_positive_int($data['quantity']) : 1;
if ($productId === null || $quantity === null || $quantity > 999) {
    cocoon_cart_json(['success' => false, 'message' => 'Sản phẩm hoặc số lượng không hợp lệ.'], 422);
}

try {
    $pdo = cocoon_pdo();
    $stmt = $pdo->prepare('SELECT id, stock FROM products WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
    if (!$product) {
        cocoon_cart_json(['success' => false, 'message' => 'Không tìm thấy sản phẩm này.'], 404);
    }
    $currentQuantity = (int) ($_SESSION['cart'][$productId] ?? 0);
    $newQuantity = $currentQuantity + $quantity;
    if ((int) $product['stock'] < 1 || $newQuantity > (int) $product['stock']) {
        cocoon_cart_json(['success' => false, 'message' => 'Số lượng yêu cầu vượt quá tồn kho hiện tại.'], 409);
    }
    $_SESSION['cart'][$productId] = $newQuantity;
    $snapshot = cocoon_cart_snapshot($pdo);
    cocoon_cart_json(['success' => true, 'message' => 'Đã thêm sản phẩm vào giỏ hàng.', 'data' => $snapshot]);
} catch (Throwable $exception) {
    error_log('[Cocoon cart_add] ' . $exception->getMessage());
    cocoon_cart_json(['success' => false, 'message' => 'Không thể thêm sản phẩm vào giỏ hàng.'], 500);
}

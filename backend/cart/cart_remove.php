<?php
require_once __DIR__ . '/cart_common.php';
cocoon_cart_require_post();
$data = cocoon_cart_request_data();
cocoon_cart_require_csrf($data);
$productId = cocoon_cart_parse_positive_int($data['product_id'] ?? null);
if ($productId === null) {
    cocoon_cart_json(['success' => false, 'message' => 'Mã sản phẩm không hợp lệ.'], 422);
}
unset($_SESSION['cart'][$productId]);
try {
    cocoon_cart_json(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.', 'data' => cocoon_cart_snapshot(cocoon_pdo())]);
} catch (Throwable $exception) {
    error_log('[Cocoon cart_remove] ' . $exception->getMessage());
    cocoon_cart_json(['success' => false, 'message' => 'Không thể cập nhật giỏ hàng.'], 500);
}

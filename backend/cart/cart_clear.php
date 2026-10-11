<?php
require_once __DIR__ . '/cart_common.php';
cocoon_cart_require_post();
$data = cocoon_cart_request_data();
cocoon_cart_require_csrf($data);
$_SESSION['cart'] = [];
cocoon_cart_json(['success' => true, 'message' => 'Đã xóa toàn bộ giỏ hàng.', 'data' => ['items' => [], 'total_items' => 0, 'subtotal' => 0]]);

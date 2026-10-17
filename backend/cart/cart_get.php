<?php
require_once __DIR__ . '/cart_common.php';

try {
    $snapshot = cocoon_cart_snapshot(cocoon_pdo());
    cocoon_cart_json(['success' => true, 'data' => $snapshot]);
} catch (Throwable $exception) {
    error_log('[Cocoon cart_get] ' . $exception->getMessage());
    cocoon_cart_json(['success' => false, 'message' => 'Không thể tải giỏ hàng lúc này. Vui lòng thử lại.'], 500);
}

<?php
/** One-time migration of the old browser cart. Prices and names are ignored. */
require_once __DIR__ . '/cart_common.php';
cocoon_cart_require_post();
$data = cocoon_cart_request_data();
cocoon_cart_require_csrf($data);
if (!empty($_SESSION['legacy_cart_imported'])) {
    cocoon_cart_json(['success' => true, 'message' => 'Giỏ hàng cũ đã được đồng bộ.', 'data' => cocoon_cart_snapshot(cocoon_pdo())]);
}
$legacyItems = $data['items'] ?? [];
if (!is_array($legacyItems) || count($legacyItems) > 50) {
    cocoon_cart_json(['success' => false, 'message' => 'Dữ liệu giỏ hàng cũ không hợp lệ.'], 422);
}
try {
    $pdo = cocoon_pdo();
    foreach ($legacyItems as $legacyItem) {
        if (!is_array($legacyItem)) {
            continue;
        }
        $id = cocoon_cart_parse_positive_int($legacyItem['id'] ?? null);
        $quantity = cocoon_cart_parse_positive_int($legacyItem['quantity'] ?? null);
        if ($id === null || $quantity === null) {
            continue;
        }
        $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();
        if (!$product || (int) $product['stock'] < 1) {
            continue;
        }
        $current = (int) ($_SESSION['cart'][$id] ?? 0);
        $_SESSION['cart'][$id] = min((int) $product['stock'], $current + min(999, $quantity));
    }
    $_SESSION['legacy_cart_imported'] = true;
    cocoon_cart_json(['success' => true, 'message' => 'Đã đồng bộ giỏ hàng cũ.', 'data' => cocoon_cart_snapshot($pdo)]);
} catch (Throwable $exception) {
    error_log('[Cocoon cart_import] ' . $exception->getMessage());
    cocoon_cart_json(['success' => false, 'message' => 'Không thể đồng bộ giỏ hàng cũ.'], 500);
}

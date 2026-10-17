<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận yêu cầu GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$search = isset($_GET['q']) && is_string($_GET['q'])
    ? trim($_GET['q'])
    : '';

if (!preg_match('/^.{2}/us', $search)) {
    echo json_encode(['success' => true, 'suggestions' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/includes/connect.php';
    require_once __DIR__ . '/product_catalog.php';

    $products = get_catalog_products($conn, 0, $search, false, 6);
    $suggestions = array_map(static function (array $product): array {
        return [
            'name' => $product['name'],
            'category' => $product['category'],
            'price' => format_vnd($product['price']),
        ];
    }, $products);

    echo json_encode(
        ['success' => true, 'suggestions' => $suggestions],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
} catch (Throwable $error) {
    error_log('Không thể tải gợi ý tìm kiếm sản phẩm: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(
        ['success' => false, 'message' => 'Không thể tải gợi ý tìm kiếm lúc này.'],
        JSON_UNESCAPED_UNICODE
    );
}

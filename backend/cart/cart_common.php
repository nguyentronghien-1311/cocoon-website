<?php
/** Shared helpers for the Cocoon session-backed cart API. */
require_once __DIR__ . '/../pdo_connect.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function cocoon_cart_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cocoon_cart_request_data(): array
{
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw === false ? '' : $raw, true);
        return is_array($data) ? $data : [];
    }
    return is_array($_POST ?? null) ? $_POST : [];
}

function cocoon_cart_csrf_valid(array $data = []): bool
{
    $headerToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $postedToken = (string) ($data['csrf_token'] ?? '');
    $token = $headerToken !== '' ? $headerToken : $postedToken;
    return $token !== '' && isset($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $token);
}

function cocoon_cart_require_post(): void
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        header('Allow: POST');
        cocoon_cart_json(['success' => false, 'message' => 'Phương thức yêu cầu không được hỗ trợ.'], 405);
    }
}

function cocoon_cart_parse_positive_int(mixed $value): ?int
{
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }
    if (!is_string($value) && !is_float($value)) {
        return null;
    }
    $string = trim((string) $value);
    if ($string === '' || !preg_match('/^[0-9]+$/', $string)) {
        return null;
    }
    $number = (int) $string;
    return $number > 0 ? $number : null;
}

function cocoon_cart_placeholder_image(string $name): string
{
    $label = htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160">'
        . '<rect width="160" height="160" rx="12" fill="#F4EEE6" />'
        . '<circle cx="80" cy="58" r="25" fill="#D7C7B8" />'
        . '<text x="80" y="104" text-anchor="middle" font-family="Arial,sans-serif" font-size="11" fill="#5C4033">the cocoon</text>'
        . '<text x="80" y="125" text-anchor="middle" font-family="Arial,sans-serif" font-size="8" fill="#5C4033">' . $label . '</text>'
        . '</svg>';
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function cocoon_cart_image_url(?string $image): string
{
    $image = trim((string) $image);
    if ($image === '') {
        return '';
    }
    if (preg_match('~^(https?://|data:image/)~i', $image) || str_starts_with($image, '/')) {
        return $image;
    }
    $file = basename($image);
    if ($file === '.' || $file === '..') {
        return '';
    }
    return is_file(dirname(__DIR__, 2) . '/images/' . $file) ? 'images/' . $file : '';
}

/** Return database-backed cart items and totals. */
function cocoon_cart_snapshot(PDO $pdo): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart) || $cart === []) {
        $_SESSION['cart'] = [];
        return ['items' => [], 'total_items' => 0, 'subtotal' => 0];
    }

    $normalized = [];
    foreach ($cart as $id => $quantity) {
        $productId = cocoon_cart_parse_positive_int((string) $id);
        $qty = cocoon_cart_parse_positive_int($quantity);
        if ($productId !== null && $qty !== null) {
            $normalized[$productId] = $qty;
        }
    }
    $_SESSION['cart'] = $normalized;
    if ($normalized === []) {
        return ['items' => [], 'total_items' => 0, 'subtotal' => 0];
    }

    $ids = array_keys($normalized);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.id, p.name, p.price, p.image, p.volume, p.stock, c.name AS category
         FROM products p LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id IN ($placeholders)"
    );
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll() as $product) {
        $products[(int) $product['id']] = $product;
    }

    $items = [];
    $totalItems = 0;
    $subtotal = 0;
    foreach ($normalized as $productId => $quantity) {
        if (!isset($products[$productId])) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }
        $product = $products[$productId];
        $price = (int) $product['price'];
        $lineTotal = $price * $quantity;
        $items[] = [
            'id' => $productId,
            'name' => (string) $product['name'],
            'category' => (string) ($product['category'] ?? ''),
            'price' => $price,
            'image' => cocoon_cart_image_url($product['image'] ?? '') ?: cocoon_cart_placeholder_image((string) $product['name']),
            'volume' => (string) ($product['volume'] ?? ''),
            'quantity' => $quantity,
            'stock' => (int) $product['stock'],
            'line_total' => $lineTotal,
        ];
        $totalItems += $quantity;
        $subtotal += $lineTotal;
    }

    return ['items' => $items, 'total_items' => $totalItems, 'subtotal' => $subtotal];
}

function cocoon_cart_require_csrf(array $data): void
{
    if (!cocoon_cart_csrf_valid($data)) {
        cocoon_cart_json(['success' => false, 'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.'], 419);
    }
}

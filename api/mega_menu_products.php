<?php
header('Content-Type: application/json; charset=utf-8');

require dirname(__DIR__) . '/includes/connect.php';
require_once dirname(__DIR__) . '/includes/mega_menu_data.php';

function send_mega_menu_json(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!($conn instanceof mysqli)) {
    error_log('Mega menu product endpoint could not connect to the database.');
    send_mega_menu_json(['error' => 'Không thể kết nối dữ liệu sản phẩm.'], 500);
}

$categoryInput = !isset($_GET['category_id'])
    ? 0
    : (is_string($_GET['category_id'])
        ? filter_var($_GET['category_id'], FILTER_VALIDATE_INT)
        : false);
if ($categoryInput === false || $categoryInput < 0) {
    send_mega_menu_json(['error' => 'Danh mục không hợp lệ.'], 400);
}
$categoryId = (int) $categoryInput;

$ingredient = !isset($_GET['ingredient'])
    ? ''
    : (is_string($_GET['ingredient']) ? trim($_GET['ingredient']) : false);
if ($ingredient === false) {
    send_mega_menu_json(['error' => 'Nguyên liệu không hợp lệ.'], 400);
}
if (mb_strlen($ingredient, 'UTF-8') > 100) {
    send_mega_menu_json(['error' => 'Nguyên liệu không hợp lệ.'], 400);
}

try {
    $categories = get_mega_menu_categories($conn);
    $categoryExists = $categoryId === 0;
    foreach ($categories as $category) {
        if ((int) $category['id'] === $categoryId) {
            $categoryExists = true;
            break;
        }
    }

    if (!$categoryExists) {
        send_mega_menu_json(['error' => 'Danh mục không tồn tại.'], 404);
    }

    $ingredients = get_mega_menu_ingredients();
} catch (RuntimeException $error) {
    error_log($error->getMessage());
    send_mega_menu_json(['error' => 'Không thể tải dữ liệu menu.'], 500);
}

if ($ingredient !== '') {
    $featuredIngredients = get_mega_menu_featured_ingredients();
    $ingredientKey = null;
    foreach ($featuredIngredients as $name => $aliases) {
        if (mb_strtolower($name, 'UTF-8') === mb_strtolower($ingredient, 'UTF-8')) {
            $ingredientKey = $name;
            break;
        }
    }

    if ($ingredientKey === null) {
        send_mega_menu_json(['error' => 'Nguyên liệu không tồn tại trong danh mục này.'], 404);
    }
    $ingredient = $ingredientKey;
}

$sql = 'SELECT id, name, price, image, volume, ingredients FROM products WHERE 1 = 1';
$types = '';
$parameters = [];
if ($categoryId > 0) {
    $sql .= ' AND category_id = ?';
    $types .= 'i';
    $parameters[] = $categoryId;
}
if ($ingredient !== '') {
    $aliases = get_mega_menu_featured_ingredients()[$ingredient];
    $alternatives = array_map(
        static fn(string $alias): string => preg_quote(mb_strtolower($alias, 'UTF-8'), '~'),
        $aliases
    );
    $ingredientPattern = '(^|[,;|])[[:space:]]*(chiết xuất[[:space:]]+|tinh dầu[[:space:]]+|hạt[[:space:]]+|nước cất[[:space:]]+)?(?:'
        . implode('|', $alternatives)
        . ')([[:space:]]*\\([^)]*\\))?[[:space:]]*([,;|]|$)';
    $sql .= ' AND ingredients IS NOT NULL AND LOWER(ingredients) REGEXP ?';
    $types .= 's';
    $parameters[] = $ingredientPattern;
}
$sql .= ' ORDER BY is_featured DESC, created_at DESC, id DESC LIMIT 4';

$statement = $conn->prepare($sql);
if (!$statement) {
    error_log('Unable to prepare mega menu product query: ' . $conn->error);
    send_mega_menu_json(['error' => 'Không thể tải sản phẩm lúc này.'], 500);
}
if ($types !== '') {
    $statement->bind_param($types, ...$parameters);
}
if (!$statement->execute()) {
    error_log('Unable to execute mega menu product query: ' . $statement->error);
    send_mega_menu_json(['error' => 'Không thể tải sản phẩm lúc này.'], 500);
}

$result = $statement->get_result();
$products = [];
while ($product = $result->fetch_assoc()) {
    $imageName = basename((string) $product['image']);
    $imagePath = dirname(__DIR__) . '/assets/images/' . $imageName;
    $products[] = [
        'id' => (int) $product['id'],
        'name' => $product['name'],
        'price' => (int) $product['price'],
        'image' => is_file($imagePath) ? 'assets/images/' . rawurlencode($imageName) : null,
        'volume' => (string) ($product['volume'] ?? ''),
        'ingredients' => (string) ($product['ingredients'] ?? ''),
        'url' => 'products.php?search=' . rawurlencode($product['name']),
    ];
}

send_mega_menu_json([
    'categoryId' => $categoryId,
    'ingredients' => $ingredients,
    'products' => $products,
]);

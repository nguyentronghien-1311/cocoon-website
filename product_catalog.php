<?php

function format_vnd($price) {
    return number_format((int) $price, 0, ',', '.') . ' ₫';
}

function svg_data_uri($svg) {
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function product_illustration($product) {
    $type  = $product['shape'];
    $c1    = $product['color1'];
    $c2    = $product['color2'];
    $label = htmlspecialchars($product['short'], ENT_XML1);
    $bg    = '#F4EEE6';

    if ($type === 'bottle') {
        $body = '
            <rect x="118" y="42" width="44" height="18" rx="4" fill="' . $c2 . '"/>
            <rect x="126" y="28" width="28" height="18" rx="3" fill="#5C4033"/>
            <rect x="94" y="60" width="92" height="210" rx="22" fill="' . $c1 . '"/>
            <rect x="102" y="78" width="76" height="150" rx="8" fill="#fff" opacity="0.22"/>
            <ellipse cx="140" cy="250" rx="28" ry="8" fill="#fff" opacity="0.18"/>
            <rect x="112" y="118" width="56" height="72" rx="4" fill="#FAF8F3" opacity="0.92"/>
            <text x="140" y="148" text-anchor="middle" font-family="Georgia, serif" font-size="9" fill="#5C4033">the cocoon</text>
            <text x="140" y="166" text-anchor="middle" font-family="Arial, sans-serif" font-size="7" fill="#6D4C41">' . $label . '</text>';
    } elseif ($type === 'jar') {
        $body = '
            <ellipse cx="140" cy="78" rx="58" ry="14" fill="' . $c2 . '"/>
            <rect x="86" y="78" width="108" height="22" rx="4" fill="' . $c2 . '"/>
            <rect x="92" y="98" width="96" height="132" rx="10" fill="' . $c1 . '"/>
            <rect x="104" y="128" width="72" height="64" rx="4" fill="#FAF8F3" opacity="0.9"/>
            <text x="140" y="156" text-anchor="middle" font-family="Georgia, serif" font-size="9" fill="#5C4033">the cocoon</text>
            <text x="140" y="174" text-anchor="middle" font-family="Arial, sans-serif" font-size="7" fill="#6D4C41">' . $label . '</text>
            <ellipse cx="140" cy="232" rx="48" ry="10" fill="#000" opacity="0.06"/>';
    } elseif ($type === 'tube') {
        $body = '
            <rect x="118" y="36" width="44" height="28" rx="6" fill="' . $c2 . '"/>
            <polygon points="118,64 162,64 154,86 126,86" fill="' . $c2 . '"/>
            <rect x="108" y="84" width="64" height="176" rx="18" fill="' . $c1 . '"/>
            <rect x="118" y="124" width="44" height="70" rx="4" fill="#FAF8F3" opacity="0.9"/>
            <text x="140" y="154" text-anchor="middle" font-family="Georgia, serif" font-size="8" fill="#5C4033">the cocoon</text>
            <text x="140" y="172" text-anchor="middle" font-family="Arial, sans-serif" font-size="6.5" fill="#6D4C41">' . $label . '</text>';
    } elseif ($type === 'spray') {
        $body = '
            <rect x="132" y="22" width="16" height="28" rx="3" fill="#5C4033"/>
            <path d="M148 30 h18 v6 h-18 z" fill="#8D6E63"/>
            <rect x="124" y="48" width="32" height="16" rx="4" fill="' . $c2 . '"/>
            <rect x="108" y="64" width="64" height="188" rx="20" fill="' . $c1 . '"/>
            <rect x="118" y="110" width="44" height="72" rx="4" fill="#FAF8F3" opacity="0.9"/>
            <text x="140" y="142" text-anchor="middle" font-family="Georgia, serif" font-size="8" fill="#5C4033">the cocoon</text>
            <text x="140" y="160" text-anchor="middle" font-family="Arial, sans-serif" font-size="6.5" fill="#6D4C41">' . $label . '</text>';
    } else {
        $body = '
            <rect x="90" y="110" width="100" height="70" rx="8" fill="' . $c1 . '"/>
            <rect x="96" y="116" width="88" height="58" rx="6" fill="' . $c2 . '"/>
            <text x="140" y="148" text-anchor="middle" font-family="Georgia, serif" font-size="9" fill="#FAF8F3">the cocoon</text>
            <text x="140" y="164" text-anchor="middle" font-family="Arial, sans-serif" font-size="7" fill="#FFF8E7">' . $label . '</text>';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 320" width="280" height="320">
        <rect width="280" height="320" fill="' . $bg . '"/>
        <ellipse cx="140" cy="292" rx="70" ry="10" fill="#D7CCC8" opacity="0.55"/>
        ' . $body . '
    </svg>';

    return svg_data_uri($svg);
}

function get_catalog_products(mysqli $conn, int $categoryId = 0, string $search = '', bool $featuredOnly = false, ?int $limit = null): array {
    $sql = "SELECT p.id, p.name, p.price, p.image, p.description,
                   c.name AS category
            FROM products AS p
            JOIN categories AS c ON p.category_id = c.id
            WHERE (? = 0 OR p.category_id = ?)
              AND p.name LIKE ?";
    if ($featuredOnly) {
        $sql .= " AND p.is_featured = 1";
    }
    $sql .= " ORDER BY p.id";
    if ($limit !== null) {
        $sql .= " LIMIT " . max(1, $limit);
    }

    $stmt = $conn->prepare($sql);
    $searchPattern = '%' . $search . '%';
    $stmt->bind_param('iis', $categoryId, $categoryId, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['price'] = (int) $row['price'];
        $row['desc'] = $row['description'] ?? '';
        $row['badge'] = '';
        $row['short'] = 'Cocoon';
        $row['shape'] = 'box';
        $row['color1'] = '#8FA87A';
        $row['color2'] = '#C9D9B8';

        $imageName = basename($row['image']);
        $row['image'] = is_file(__DIR__ . '/images/' . $imageName)
            ? 'images/' . $imageName
            : product_illustration($row);

        $row['price_text'] = format_vnd($row['price']);
        $products[] = $row;
    }

    return $products;
}

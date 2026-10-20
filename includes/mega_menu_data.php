<?php

function get_mega_menu_categories(mysqli $conn): array
{
    $result = $conn->query('SELECT id, name FROM categories ORDER BY name');
    if (!$result) {
        throw new RuntimeException('Unable to load mega menu categories: ' . $conn->error);
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_mega_menu_featured_ingredients(): array
{
    return [
        'Bí Đao' => ['bí đao'],
        'Sen Hậu Giang' => ['sen hậu giang', 'sen'],
        'Bưởi' => ['vỏ bưởi', 'bưởi'],
        'Cà Phê Đắk Lắk' => ['cà phê đắk lắk'],
        'Nghệ Hưng Yên' => ['nghệ hưng yên', 'nghệ'],
        'Hoa Hồng Cao Bằng' => ['hoa hồng cao bằng', 'hoa hồng'],
        'Dầu Dừa Bến Tre' => ['dầu dừa bến tre'],
    ];
}

function get_mega_menu_ingredients(): array
{
    return array_keys(get_mega_menu_featured_ingredients());
}

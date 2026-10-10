<?php
/**
 * Trang danh sách sản phẩm — Website mỹ phẩm thuần chay Cocoon Việt Nam
 * Bài tập nhóm môn Thiết kế Web – TMU
 * File duy nhất: products.php (chạy trên XAMPP)
 */

session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function format_vnd($price) {
    return number_format((int) $price, 0, ',', '.') . ' ₫';
}

function svg_data_uri($svg) {
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

/**
 * Minh họa sản phẩm bằng SVG (không phụ thuộc file ảnh ngoài, không bị vỡ).
 */
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


require __DIR__ . '/connect.php';

$search = isset($_GET['search']) && is_string($_GET['search'])
    ? trim($_GET['search']) : '';

$categoryId = isset($_GET['category']) && in_array($_GET['category'], ['1', '2', '3'], true)
    ? (int) $_GET['category'] : 0;

$sql = "SELECT p.id, p.name, p.price, p.image, p.description,
               c.name AS category
        FROM products AS p
        JOIN categories AS c ON p.category_id = c.id
        WHERE (? = 0 OR p.category_id = ?)
          AND p.name LIKE ?
        ORDER BY p.id";

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

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm | the cocoon Vietnam</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #2E7D32;
            --green-dark: #1B5E20;
            --brown: #8D6E63;
            --turmeric: #E8C547;
            --bg: #FAF8F3;
            --bg-soft: #F3EEE6;
            --white: #FFFFFF;
            --text: #222222;
            --text-2: #333333;
            --muted: #666666;
            --line: #E6E0D6;
            --radius: 10px;
            --shadow: 0 8px 24px rgba(62, 48, 36, 0.06);
            --header-h: 88px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: Inter, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.55;
            min-height: 100vh;
        }
        img { max-width: 100%; display: block; }
        a { color: inherit; text-decoration: none; }
        button, input, select { font-family: inherit; }
        button { cursor: pointer; }
        .container { width: min(1280px, calc(100% - 40px)); margin: 0 auto; }

        /* Announcement */
        .announce {
            background: #F0EBE1;
            color: var(--text-2);
            font-size: 13px;
            letter-spacing: 0.02em;
            text-align: center;
            padding: 10px 16px;
        }
        .announce span { opacity: 0.7; margin-left: 6px; }

        /* Header */
        .header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(250, 248, 243, 0.96);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line);
        }
        .header-inner {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            min-height: var(--header-h);
            gap: 16px;
        }
        .nav {
            display: flex;
            gap: 22px;
            align-items: center;
        }
        .nav a {
            font-size: 14px;
            color: var(--text-2);
            position: relative;
            padding: 6px 0;
        }
        .nav a:hover,
        .nav a.is-active { color: var(--green-dark); }
        .nav a.is-active::after {
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 1px;
            background: var(--green);
        }
        .logo {
            text-align: center;
            padding: 10px 0;
        }
        .logo .brand {
            font-family: "Cormorant Garamond", Georgia, serif;
            font-style: italic;
            font-size: 34px;
            line-height: 0.9;
            color: #3E3228;
            font-weight: 500;
            display: block;
        }
        .logo .sub {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            letter-spacing: 0.38em;
            color: var(--brown);
        }
        .header-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 6px;
        }
        .icon-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            border: 0;
            color: var(--text-2);
            padding: 8px 10px;
            border-radius: 999px;
            font-size: 13px;
            transition: background 0.2s, color 0.2s;
        }
        .icon-btn:hover { background: var(--bg-soft); color: var(--green-dark); }
        .icon-btn:active { transform: translateY(1px); }
        .icon-btn svg { width: 20px; height: 20px; }
        .cart-btn .count {
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--green);
            color: #fff;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .lang { font-weight: 600; letter-spacing: 0.08em; }
        .menu-toggle { display: inline-flex; }

        /* Breadcrumb + hero */
        .page-head { padding: 36px 0 18px; }
        .crumb {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 28px;
        }
        .crumb a:hover { color: var(--green); }
        .page-head h1 {
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(36px, 5vw, 52px);
            font-weight: 500;
            letter-spacing: 0.18em;
            text-align: center;
            color: #2C241C;
        }
        .page-head p {
            max-width: 620px;
            margin: 14px auto 0;
            text-align: center;
            color: var(--muted);
            font-size: 15px;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0 22px;
        }
        .cats { display: flex; flex-wrap: wrap; gap: 8px; }
        .cat-btn {
            border: 1px solid var(--line);
            background: transparent;
            color: var(--text-2);
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 13px;
            transition: 0.22s;
        }
        .cat-btn:hover { border-color: var(--brown); color: var(--brown); }
        .cat-btn.active {
            background: var(--green);
            border-color: var(--green);
            color: #fff;
        }
        .tools {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-left: auto;
        }
        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 8px 8px 14px;
            min-width: min(320px, 100%);
        }
        .search-box:focus-within { border-color: var(--green); box-shadow: 0 0 0 3px rgba(46,125,50,.12); }
        .search-box input {
            border: 0;
            outline: 0;
            background: transparent;
            width: 100%;
            font-size: 14px;
            color: var(--text);
        }
        .search-box button {
            background: var(--green);
            color: #fff;
            border: 0;
            border-radius: 999px;
            padding: 7px 14px;
            font-size: 13px;
            transition: background 0.2s;
        }
        .search-box button:hover { background: var(--green-dark); }
        .search-box button:disabled { opacity: 0.5; cursor: not-allowed; }
        .sort-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }
        .sort-wrap select {
            border: 1px solid var(--line);
            background: var(--white);
            border-radius: 8px;
            padding: 8px 10px;
            color: var(--text-2);
            outline: none;
        }
        .sort-wrap select:focus { border-color: var(--green); }
        .result-count {
            width: 100%;
            font-size: 13px;
            color: var(--muted);
        }

        /* Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
            padding-bottom: 64px;
        }
        .card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 32px rgba(62, 48, 36, 0.1);
        }
        .card-media {
            position: relative;
            background: var(--bg-soft);
            aspect-ratio: 4 / 5;
            overflow: hidden;
        }
        .card-media img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        .card:hover .card-media img { transform: scale(1.04); }
        .badge {
            position: absolute;
            top: 12px;
            left: 12px;
            font-size: 10px;
            letter-spacing: 0.08em;
            background: #FFF8E7;
            color: #6D4C41;
            border: 1px solid #E8D9B0;
            padding: 4px 8px;
            border-radius: 999px;
        }
        .badge.sale { background: #F3E5D8; color: #8D4B32; border-color: #E0C4B0; }
        .badge.new { background: #E8F5E9; color: var(--green-dark); border-color: #C8E6C9; }
        .wish {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 0;
            background: rgba(255,255,255,.88);
            color: var(--brown);
            display: grid;
            place-items: center;
            transition: 0.2s;
        }
        .wish:hover, .wish.active { color: #C62828; background: #fff; }
        .wish.active svg { fill: #C62828; }
        .card-body { padding: 16px 16px 18px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .card-cat { font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--brown); }
        .card-body h3 { font-size: 16px; font-weight: 600; line-height: 1.35; color: var(--text); }
        .card-body .desc { font-size: 13px; color: var(--muted); min-height: 38px; }
        .price {
            font-size: 17px;
            font-weight: 600;
            color: var(--green-dark);
            margin-top: 4px;
        }
        .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: auto; padding-top: 8px; }
        .btn {
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid transparent;
            transition: 0.2s;
        }
        .btn-ghost {
            background: transparent;
            border-color: var(--line);
            color: var(--text-2);
        }
        .btn-ghost:hover { border-color: var(--green); color: var(--green-dark); }
        .btn-primary {
            background: var(--green);
            color: #fff;
        }
        .btn-primary:hover { background: var(--green-dark); }
        .btn:active { transform: translateY(1px); }
        .btn:disabled { opacity: 0.45; cursor: not-allowed; }

        .empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
            display: none;
        }
        .empty.show { display: block; }

        /* Footer */
        footer {
            background: #F3EEE6;
            border-top: 1px solid var(--line);
            padding: 48px 0 0;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr 1fr;
            gap: 32px;
            padding-bottom: 36px;
        }
        .footer-brand .brand {
            font-family: "Cormorant Garamond", Georgia, serif;
            font-style: italic;
            font-size: 28px;
        }
        .footer-brand p { color: var(--muted); margin-top: 10px; max-width: 260px; font-size: 14px; }
        footer h4 {
            font-size: 13px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 14px;
            color: #3E3228;
        }
        footer ul { list-style: none; display: grid; gap: 8px; }
        footer li, .socials button {
            font-size: 14px;
            color: var(--muted);
            background: none;
            border: 0;
            padding: 0;
            text-align: left;
        }
        footer a:hover, .socials button:hover { color: var(--green); }
        .copy {
            border-top: 1px solid var(--line);
            padding: 16px 0;
            font-size: 12px;
            color: var(--muted);
            text-align: center;
        }

        /* Toast */
        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            background: #2C241C;
            color: #fff;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,.18);
            transform: translateY(20px);
            opacity: 0;
            pointer-events: none;
            transition: 0.25s;
            z-index: 80;
        }
        .toast.show { opacity: 1; transform: none; }

        /* Modal */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(40, 32, 24, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 70;
        }
        .overlay.show { display: flex; }
        .modal {
            width: min(860px, 100%);
            background: var(--white);
            border-radius: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            position: relative;
            max-height: calc(100vh - 40px);
        }
        .modal-media { background: var(--bg-soft); display: grid; place-items: center; padding: 24px; }
        .modal-media img { width: 100%; max-height: 420px; object-fit: contain; }
        .modal-body { padding: 32px 28px; overflow: auto; }
        .modal-body .cat { color: var(--brown); font-size: 12px; letter-spacing: 0.1em; text-transform: uppercase; }
        .modal-body h2 { font-size: 26px; margin: 10px 0 12px; font-weight: 600; }
        .modal-body .price { font-size: 22px; margin-bottom: 14px; }
        .close-x {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 50%;
            background: #fff;
            font-size: 20px;
            color: var(--text-2);
        }
        .mobile-drawer {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 60;
            background: rgba(0,0,0,.35);
        }
        .mobile-drawer.show { display: block; }
        .drawer {
            width: min(320px, 86%);
            height: 100%;
            background: var(--bg);
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .drawer a { font-size: 16px; }
        .drawer form { margin: 0; }
        .drawer .logout-button {
            padding: 0;
            border: 0;
            background: none;
            color: var(--text-2);
            font: inherit;
            font-size: 16px;
            text-align: left;
            cursor: pointer;
        }
        .drawer .logout-button:hover { color: var(--green); }

        *:focus-visible {
            outline: 2px solid var(--green);
            outline-offset: 2px;
        }

        @media (max-width: 1200px) {
            .grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 1024px) {
            .header-inner { grid-template-columns: auto 1fr auto; }
            .nav { display: none; }
            .icon-label { display: none; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
            .modal { grid-template-columns: 1fr; overflow: auto; }
            .modal-media { padding: 10px; }
            .tools { width: 100%; margin-left: 0; }
            .search-box { min-width: 100%; }
            .logo .brand { font-size: 28px; }
        }
        @media (max-width: 480px) {
            .container { width: calc(100% - 24px); }
            .actions { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; }
            .page-head { padding-top: 24px; }
            .card-body h3 { font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="announce">
        Tận hưởng giao hàng miễn phí toàn quốc với hóa đơn từ 99.000đ <span aria-hidden="true">+</span>
    </div>

    <header class="header">
        <div class="container header-inner">
            <nav class="nav" aria-label="Menu chính">
                <a href="#products" class="is-active">Sản phẩm</a>
                <a href="#products">Khuyến mãi</a>
                <a href="#about">Cocoon</a>
            </nav>

            <a class="logo" href="index.php" id="top">
                <span class="brand">the cocoon</span>
                <span class="sub">VIETNAM</span>
            </a>

            <div class="header-actions">
                <button class="icon-btn" type="button" aria-label="Tìm kiếm" id="focusSearch">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                </button>
                <?php if (!isset($_SESSION['user_id'])): ?>
                <a class="icon-btn" href="login.php" aria-label="Đăng nhập hoặc đăng ký">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="3.2"/><path d="M5 19c1.5-3.2 4-4.8 7-4.8S17.5 15.8 19 19"/></svg>
                    <span class="icon-label">Đăng nhập/Đăng ký</span>
                </a>
                <?php endif; ?>
                <button class="icon-btn" type="button" aria-label="Liên hệ">
                    <span class="icon-label">Liên hệ</span>
                </button>
                <a class="icon-btn cart-btn" href="cart.php" aria-label="Giỏ hàng">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M6 8h12l-1 11H7L6 8z"/><path d="M9 8V7a3 3 0 016 0v1"/></svg>
                    <span class="icon-label">Giỏ hàng</span>
                    <span class="count" id="cartCount">0</span>
                </a>
                <button class="icon-btn lang" type="button" aria-label="Ngôn ngữ">EN</button>
                <button class="icon-btn menu-toggle" type="button" aria-label="Mở menu" aria-controls="mobileDrawer" aria-expanded="false" id="openMenu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>
    </header>

    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer" role="dialog" aria-label="Menu điện thoại">
            <button class="icon-btn" type="button" id="closeMenu" aria-label="Đóng menu">✕ Đóng</button>
            <a href="#products">Sản phẩm</a>
            <a href="#products">Khuyến mãi</a>
            <a href="#about">Cocoon</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="addresses.php">Địa chỉ giao hàng</a>
                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <button class="logout-button" type="submit">Đăng xuất</button>
                </form>
            <?php else: ?>
                <a href="login.php">Đăng nhập</a>
                <a href="register.php">Đăng ký</a>
            <?php endif; ?>
            <a href="#footer">Liên hệ</a>
        </div>
    </div>

    <main>
        <section class="page-head container">
            <nav class="crumb" aria-label="Breadcrumb">
                <a href="index.php">Trang chủ</a> / <span>Sản phẩm</span>
            </nav>
            <h1>SẢN PHẨM</h1>
            <p>Khám phá các sản phẩm chăm sóc da, tóc và cơ thể thuần chay từ những nguyên liệu thiên nhiên Việt Nam.</p>
        </section>

        <section class="container" id="products">
            <div class="toolbar">
                <div class="cats" role="tablist" aria-label="Danh mục sản phẩm">
                   <button class="cat-btn <?= $categoryId === 0 ? 'active' : '' ?>" type="button" data-cat="0">Tất cả</button>
<button class="cat-btn <?= $categoryId === 1 ? 'active' : '' ?>" type="button" data-cat="1">Chăm sóc da</button>
<button class="cat-btn <?= $categoryId === 2 ? 'active' : '' ?>" type="button" data-cat="2">Chăm sóc tóc</button>
<button class="cat-btn <?= $categoryId === 3 ? 'active' : '' ?>" type="button" data-cat="3">Chăm sóc cơ thể</button>

                </div>
                <div class="tools">
                   <form class="search-box" id="searchForm" role="search" method="get" action="products.php">
    <input type="hidden" name="category" value="<?= $categoryId ?>">

                        <label class="visually-hidden" for="searchInput" style="position:absolute;left:-9999px;">Tìm kiếm sản phẩm</label>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8D6E63" stroke-width="1.7" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>

<input id="searchInput" name="search" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Tìm kiếm sản phẩm..." autocomplete="off">

                        <button type="submit">Tìm kiếm</button>
                    </form>
                    <div class="sort-wrap">
                        <label for="sortSelect">Sắp xếp</label>
                        <select id="sortSelect">
                            <option value="default">Mặc định</option>
                            <option value="price-asc">Giá thấp → cao</option>
                            <option value="price-desc">Giá cao → thấp</option>
                            <option value="name-asc">Tên A → Z</option>
                            <option value="name-desc">Tên Z → A</option>
                        </select>
                    </div>
                </div>
                <div class="result-count" id="resultCount">Hiển thị <?= count($products) ?> sản phẩm</div>
            </div>

            <div class="grid" id="productGrid">
                <?php foreach ($products as $index => $product): ?>
                    <?php
                        $badgeClass = '';
                        if ($product['badge'] === 'NEW') $badgeClass = 'new';
                        if ($product['badge'] === 'SALE') $badgeClass = 'sale';
                    ?>
                    <article
                        class="card"
                        data-id="<?= (int) $product['id'] ?>"
                        data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-category="<?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?>"
                        data-price="<?= (int) $product['price'] ?>"
                        data-index="<?= (int) $index ?>"
                    >
                        <div class="card-media">
                            <img src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($product['badge'] !== ''): ?>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($product['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <button class="wish" type="button" aria-label="Yêu thích <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 20s-7-4.4-9.2-8.2C1 8.7 3.2 5 7 5c2 0 3.3 1 5 3 1.7-2 3-3 5-3 3.8 0 6 3.7 4.2 6.8C19 15.6 12 20 12 20z"/></svg>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="card-cat"><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></div>
                            <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="desc"><?= htmlspecialchars($product['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="price"><?= htmlspecialchars($product['price_text'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="actions">
                                <button class="btn btn-ghost js-view" type="button">Xem sản phẩm</button>
                                <button class="btn btn-primary js-add" type="button">Thêm vào giỏ</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <div class="empty" id="emptyState">Không tìm thấy sản phẩm phù hợp.</div>
            </div>
        </section>
    </main>

    <footer id="footer">
        <div class="container footer-grid">
            <div class="footer-brand" id="about">
                <div class="brand">the cocoon</div>
                <p>Thương hiệu mỹ phẩm thuần chay Việt Nam</p>
            </div>
            <div>
                <h4>Sản phẩm</h4>
                <ul>
                    <li><a href="#products">Chăm sóc da</a></li>
                    <li><a href="#products">Chăm sóc tóc</a></li>
                    <li><a href="#products">Chăm sóc cơ thể</a></li>
                </ul>
            </div>
            <div>
                <h4>Hỗ trợ</h4>
                <ul>
                    <li><a href="#footer">Liên hệ</a></li>
                    <li><a href="#footer">Chính sách giao hàng</a></li>
                    <li><a href="#footer">Chính sách đổi trả</a></li>
                </ul>
            </div>
            <div>
                <h4>Theo dõi</h4>
                <div class="socials">
                    <button type="button">Facebook</button><br>
                    <button type="button">Instagram</button><br>
                    <button type="button">TikTok</button>
                </div>
            </div>
        </div>
        <div class="copy">© 2026 Cocoon Vietnam. All rights reserved.</div>
    </footer>

    <div class="overlay" id="overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal">
            <button class="close-x" type="button" id="closeModal" aria-label="Đóng">×</button>
            <div class="modal-media">
                <img id="modalImage" alt="">
            </div>
            <div class="modal-body">
                <div class="cat" id="modalCat"></div>
                <h2 id="modalTitle"></h2>
                <div class="price" id="modalPrice"></div>
                <p id="modalDesc" style="color:#666;margin-bottom:20px;"></p>
                <button class="btn btn-primary" type="button" id="modalAdd" style="width:100%;padding:12px;">Thêm vào giỏ</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast" role="status">Đã thêm sản phẩm vào giỏ hàng!</div>

    <script>
        const productsData = <?= json_encode(array_map(function ($p) {
            return [
                'id' => $p['id'],
                'name' => $p['name'],
                'category' => $p['category'],
                'price' => $p['price'],
                'price_text' => $p['price_text'],
                'desc' => $p['desc'],
                'image' => $p['image'],
            ];
        }, $products), JSON_UNESCAPED_UNICODE) ?>;

        const grid = document.getElementById('productGrid');
        const cards = Array.from(document.querySelectorAll('.card'));
        const emptyState = document.getElementById('emptyState');
        const resultCount = document.getElementById('resultCount');
        const searchInput = document.getElementById('searchInput');
        const sortSelect = document.getElementById('sortSelect');
        const cartCountEl = document.getElementById('cartCount');
        const toast = document.getElementById('toast');
        const overlay = document.getElementById('overlay');
        const drawer = document.getElementById('mobileDrawer');


        let toastTimer = null;
        let modalProductId = null;

        function getCartItems() {
            try {
                const savedItems = JSON.parse(localStorage.getItem('cocoon_cart_items') || '[]');
                if (!Array.isArray(savedItems)) return [];

                return savedItems.map((item) => ({
                    id: Number(item.id),
                    name: String(item.name || ''),
                    category: String(item.category || ''),
                    price: Number(item.price),
                    image: String(item.image || ''),
                    quantity: Math.max(1, Math.floor(Number(item.quantity) || 1)),
                })).filter((item) => Number.isInteger(item.id) && item.id > 0 && Number.isFinite(item.price) && item.price >= 0 && item.name !== '');
            } catch {
                return [];
            }
        }

        function updateCartCount(items = getCartItems()) {
            const count = items.reduce((total, item) => total + item.quantity, 0);
            localStorage.setItem('cocoon_cart_count', String(count));
            cartCountEl.textContent = String(count);
        }

        function showToast(message) {
            toast.textContent = message;
            toast.classList.add('show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('show'), 2200);
        }

        function addToCart(productId) {
            const product = productsData.find((item) => Number(item.id) === Number(productId));
            if (!product) return;

            const items = getCartItems();
            const existingItem = items.find((item) => item.id === Number(product.id));
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                items.push({
                    id: Number(product.id),
                    name: product.name,
                    category: product.category,
                    price: Number(product.price),
                    image: product.image,
                    quantity: 1,
                });
            }
            localStorage.setItem('cocoon_cart_items', JSON.stringify(items));
            updateCartCount(items);
            showToast('Đã thêm sản phẩm vào giỏ hàng!');
        }



        function applyFilters() {
    const visible = [...cards];

            const sort = sortSelect.value;
            visible.sort((a, b) => {
                const pa = Number(a.dataset.price);
                const pb = Number(b.dataset.price);
                const na = a.dataset.name.localeCompare(b.dataset.name, 'vi');
                if (sort === 'price-asc') return pa - pb;
                if (sort === 'price-desc') return pb - pa;
                if (sort === 'name-asc') return na;
                if (sort === 'name-desc') return -na;
                return Number(a.dataset.index) - Number(b.dataset.index);
            });

            visible.forEach((card) => grid.appendChild(card));
            grid.appendChild(emptyState);
            emptyState.classList.toggle('show', visible.length === 0);
            resultCount.textContent = 'Hiển thị ' + visible.length + ' sản phẩm';
        }

        function openModal(id) {
            const item = productsData.find((p) => Number(p.id) === Number(id));
            if (!item) return;
            modalProductId = item.id;
            document.getElementById('modalImage').src = item.image;
            document.getElementById('modalImage').alt = item.name;
            document.getElementById('modalCat').textContent = item.category;
            document.getElementById('modalTitle').textContent = item.name;
            document.getElementById('modalPrice').textContent = item.price_text;
            document.getElementById('modalDesc').textContent = item.desc;
            overlay.classList.add('show');
        }

        function closeModal() {
            overlay.classList.remove('show');
            modalProductId = null;
        }


document.querySelectorAll('.cat-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        const params = new URLSearchParams();
        params.set('category', btn.dataset.cat);

        const keyword = searchInput.value.trim();
        if (keyword) params.set('search', keyword);

        window.location.href = 'products.php?' + params.toString();
    });
});




        sortSelect.addEventListener('change', applyFilters);

        grid.addEventListener('click', (e) => {
            const card = e.target.closest('.card');
            if (!card) return;
            if (e.target.closest('.wish')) {
                e.target.closest('.wish').classList.toggle('active');
                return;
            }
            if (e.target.closest('.js-add')) {
                addToCart(card.dataset.id);
                return;
            }
            if (e.target.closest('.js-view')) {
                openModal(card.dataset.id);
            }
        });

        document.getElementById('modalAdd').addEventListener('click', () => addToCart(modalProductId));
        document.getElementById('closeModal').addEventListener('click', closeModal);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });
        const menuToggle = document.getElementById('openMenu');

        function closeMenu() {
            drawer.classList.remove('show');
            menuToggle.setAttribute('aria-expanded', 'false');
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
                closeMenu();
            }
        });

        menuToggle.addEventListener('click', () => {
            drawer.classList.add('show');
            menuToggle.setAttribute('aria-expanded', 'true');
        });
        document.getElementById('closeMenu').addEventListener('click', closeMenu);
        drawer.addEventListener('click', (e) => {
            if (e.target === drawer || e.target.closest('a')) closeMenu();
        });
        document.getElementById('focusSearch').addEventListener('click', () => {
            searchInput.focus();
            searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        updateCartCount();
        applyFilters();
    </script>
</body>
</html>

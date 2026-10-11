<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;
$error = '';
$note = '';
$customerName = '';
$customerPhone = '';
$customerAddress = '';
$selectedAddressId = 0;
$addresses = [];
$successOrderId = isset($_SESSION['last_order_id']) ? (int) $_SESSION['last_order_id'] : null;
unset($_SESSION['last_order_id']);

$orderItems = [
    ['name' => 'Cà phê Đắk Lắk làm sạch da chết cơ thể', 'price' => 125000, 'quantity' => 1],
    ['name' => 'Nước dưỡng tóc tinh dầu bưởi Cocoon', 'price' => 145000, 'quantity' => 2],
];
$totalAmount = array_sum(array_map(static fn ($item) => $item['price'] * $item['quantity'], $orderItems));
$formatCurrency = static fn ($amount) => number_format((int) $amount, 0, ',', '.') . ' đ';
$pdo = null;

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=cocoon_db;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    if ($isLoggedIn) {
        $profileQuery = $pdo->prepare('SELECT full_name, phone, address FROM users WHERE id = :user_id');
        $profileQuery->execute([':user_id' => $userId]);
        $profile = $profileQuery->fetch();

        if ($profile && $profile['address'] && $profile['phone']) {
            $countQuery = $pdo->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id');
            $countQuery->execute([':user_id' => $userId]);
            if ((int) $countQuery->fetchColumn() === 0) {
                $seedAddress = $pdo->prepare(
                    'INSERT INTO user_addresses (user_id, label, recipient_name, phone, address, is_default) VALUES (:user_id, :label, :recipient_name, :phone, :address, 1)'
                );
                $seedAddress->execute([
                    ':user_id' => $userId,
                    ':label' => 'Nhà riêng',
                    ':recipient_name' => $profile['full_name'],
                    ':phone' => $profile['phone'],
                    ':address' => $profile['address'],
                ]);
            }
        }

        $addressQuery = $pdo->prepare('SELECT id, label, recipient_name, phone, address, is_default FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, id DESC');
        $addressQuery->execute([':user_id' => $userId]);
        $addresses = $addressQuery->fetchAll();
        foreach ($addresses as $savedAddress) {
            if ((int) $savedAddress['is_default'] === 1) {
                $selectedAddressId = (int) $savedAddress['id'];
                break;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $note = trim((string) ($_POST['note'] ?? ''));
        $paymentMethod = (string) ($_POST['payment_method'] ?? 'cod');

        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            $error = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
        } elseif (strlen($note) > 255) {
            $error = 'Ghi chú không được vượt quá 255 ký tự.';
        } elseif (!in_array($paymentMethod, ['cod', 'banking'], true)) {
            $error = 'Vui lòng chọn phương thức thanh toán hợp lệ.';
        } else {
            if ($isLoggedIn) {
                $selectedAddressId = (int) ($_POST['address_id'] ?? 0);
                $chosenAddress = $pdo->prepare('SELECT recipient_name, phone, address FROM user_addresses WHERE id = :id AND user_id = :user_id');
                $chosenAddress->execute([':id' => $selectedAddressId, ':user_id' => $userId]);
                $delivery = $chosenAddress->fetch();
                if (!$delivery) {
                    $error = 'Vui lòng chọn một địa chỉ giao hàng đã lưu.';
                } else {
                    $customerName = $delivery['recipient_name'];
                    $customerPhone = $delivery['phone'];
                    $customerAddress = $delivery['address'];
                }
            } else {
                $customerName = trim((string) ($_POST['customer_name'] ?? ''));
                $customerPhone = trim((string) ($_POST['customer_phone'] ?? ''));
                $customerAddress = trim((string) ($_POST['customer_address'] ?? ''));
                if ($customerName === '' || strlen($customerName) > 100 || $customerPhone === '' || strlen($customerPhone) > 20 || $customerAddress === '' || strlen($customerAddress) > 255) {
                    $error = 'Vui lòng nhập đầy đủ thông tin giao hàng hợp lệ.';
                }
            }

            if ($error === '') {
                $pdo->beginTransaction();
                $orderInsert = $pdo->prepare(
                    'INSERT INTO orders (customer_name, customer_phone, customer_address, total_amount, payment_method, user_id, note) VALUES (:name, :phone, :address, :total, :payment, :user_id, :note)'
                );
                $orderInsert->execute([
                    ':name' => $customerName,
                    ':phone' => $customerPhone,
                    ':address' => $customerAddress,
                    ':total' => $totalAmount,
                    ':payment' => $paymentMethod === 'banking' ? 'Chuyển khoản' : 'COD',
                    ':user_id' => $userId,
                    ':note' => $note !== '' ? $note : null,
                ]);
                $orderId = (int) $pdo->lastInsertId();
                $itemInsert = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (:order_id, NULL, :name, :price, :quantity)');
                foreach ($orderItems as $item) {
                    $itemInsert->execute([
                        ':order_id' => $orderId,
                        ':name' => $item['name'],
                        ':price' => $item['price'],
                        ':quantity' => $item['quantity'],
                    ]);
                }
                $pdo->commit();
                $_SESSION['last_order_id'] = $orderId;
                header('Location: checkout.php');
                exit;
            }
        }
    }
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    $error = 'Không thể xử lý đơn hàng lúc này. Vui lòng thử lại sau.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - Mỹ Phẩm Thuần Chay Cocoon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2E7D32;    /* Màu xanh lá đậm Cocoon */
            --secondary: #8D6E63;  /* Nâu mộc */
            --bg: #F9F9F6;         /* Nền be tự nhiên */
            --text: #2c3e50;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 0;
        }
        header {
            position: relative;
            z-index: 10;
            background: white;
            border-bottom: 2px solid #e0e0e0;
            padding: 15px 40px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 20px;
        }
        .logo {
            grid-column: 2;
            grid-row: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-weight: 500;
            color: #3E3228;
            text-decoration: none;
        }
        .logo-the, .logo-name { font-family: "Cormorant Garamond", Georgia, serif; font-style: italic; }
        .logo-the { margin-bottom: -4px; font-size: 13px; line-height: 1; }
        .logo-name { font-size: 28px; line-height: 0.9; }
        .logo-country { margin-top: 4px; color: #8D6E63; font-size: 8px; letter-spacing: 0.38em; }
        .site-menu { position: relative; grid-column: 3; grid-row: 1; justify-self: end; }
        .tagline { grid-column: 1; grid-row: 1; }
        .menu-toggle {
            width: 42px;
            height: 42px;
            display: grid;
            align-content: center;
            justify-items: center;
            gap: 5px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: white;
            cursor: pointer;
        }
        .menu-toggle span { width: 20px; height: 2px; background: var(--primary); }
        .menu-panel {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 190px;
            padding: 6px;
            border: 1px solid #e0e0e0;
            background: white;
            box-shadow: 0 8px 22px rgba(0,0,0,.12);
        }
        .menu-panel[hidden] { display: none; }
        .menu-panel a { display: block; padding: 10px 12px; color: var(--text); text-decoration: none; }
        .menu-panel a:hover { background: var(--bg); color: var(--primary); }
        .menu-panel form { margin: 0; }
        .menu-panel button { width: 100%; padding: 10px 12px; border: 0; background: transparent; color: var(--text); font: inherit; text-align: left; cursor: pointer; }
        .menu-panel button:hover { background: var(--bg); color: var(--primary); }
        @media (max-width: 600px) {
            header { padding: 14px 18px; }
            .tagline { display: none; }
        }
        .container {
            max-width: 1000px;
            margin: 35px auto;
            display: flex;
            gap: 30px;
        }
        /* Cột bên trái: Form điền thông tin */
        .checkout-form {
            flex: 1.6;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        /* Cột bên phải: Tóm tắt đơn hàng */
        .order-summary {
            flex: 1.1;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            height: fit-content;
        }
        h2 {
            font-size: 20px;
            color: var(--primary);
            margin-top: 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            font-size: 14px;
        }
        input[type="text"], input[type="tel"], textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #fff;
            font-size: 14px;
        }
        input:focus, textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        select:focus { border-color: var(--primary); outline: none; }
        .notice { margin-bottom: 18px; padding: 12px 14px; border-radius: 6px; background: #edf5ed; color: #256629; }
        .error { margin-bottom: 18px; padding: 12px 14px; border-radius: 6px; background: #fff1f1; color: #b71c1c; }
        .manage-address { display: inline-block; margin-top: 10px; color: var(--primary); font-size: 14px; }
        .manage-address:hover { text-decoration: underline; }
        .payment-methods {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .payment-option {
            border: 1px solid #ddd;
            padding: 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .payment-option:hover {
            border-color: var(--primary);
            background-color: #f6fbf6;
        }
        /* Danh sách tóm tắt hàng bên phải */
        .item-list {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 10px;
        }
        .price-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .total-price {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary);
            border-top: 1px solid #eee;
            padding-top: 12px;
        }
        .btn-order {
            width: 100%;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn-order:hover {
            background-color: #1b5e20;
        }
        .btn-order:disabled { background: #9eaaa0; cursor: not-allowed; }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: var(--secondary);
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo" aria-label="the cocoon Vietnam - Trang chủ">
            <span class="logo-the">the</span>
            <span class="logo-name">cocoon</span>
            <span class="logo-country">VIETNAM</span>
        </a>
        <span class="tagline">Mỹ phẩm thuần chay 100% Việt Nam</span>
        <div class="site-menu">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Mở menu" aria-controls="menuPanel" aria-expanded="false"><span></span><span></span><span></span></button>
            <nav class="menu-panel" id="menuPanel" aria-label="Menu chính" hidden>
                <a href="index.php">Trang chủ</a>
                <a href="products.php">Sản phẩm</a>
                <a href="cart.php">Giỏ hàng</a>
                <a href="saved-coupons.php">Mã giảm giá đã lưu</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="addresses.php">Địa chỉ giao hàng</a>
                    <form action="logout.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Đăng xuất</button>
                    </form>
                <?php else: ?>
                    <a href="login.php">Đăng nhập</a>
                    <a href="register.php">Đăng ký</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Cột trái: Thông tin nhận hàng -->
        <div class="checkout-form">
            <h2>Thông Tin Giao Hàng</h2>
            <?php if ($successOrderId): ?>
                <div class="notice" role="status">Đặt hàng thành công. Mã đơn hàng của bạn là #<?= (int) $successOrderId ?>.</div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($isLoggedIn): ?>
                <div class="form-group">
                    <label for="address_id">Địa chỉ giao hàng</label>
                    <?php if ($addresses): ?>
                        <select id="address_id" name="address_id" required>
                            <?php foreach ($addresses as $savedAddress): ?>
                                <option value="<?= (int) $savedAddress['id'] ?>" <?= (int) $savedAddress['id'] === $selectedAddressId ? 'selected' : '' ?>><?= htmlspecialchars($savedAddress['label'] . ' · ' . $savedAddress['recipient_name'] . ' · ' . $savedAddress['phone'] . ' · ' . $savedAddress['address'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <p>Vui lòng thêm địa chỉ giao hàng trước khi thanh toán.</p>
                    <?php endif; ?>
                    <a class="manage-address" href="addresses.php">Quản lý địa chỉ giao hàng</a>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label for="customer_name">Họ và tên người nhận (*):</label>
                    <input type="text" id="customer_name" name="customer_name" required value="<?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ví dụ: Nguyễn Văn A">
                </div>
                <div class="form-group">
                    <label for="customer_phone">Số điện thoại (*):</label>
                    <input type="tel" id="customer_phone" name="customer_phone" required value="<?= htmlspecialchars($customerPhone, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ví dụ: 0987654321">
                </div>
                <div class="form-group">
                    <label for="customer_address">Địa chỉ nhận hàng chi tiết (*):</label>
                    <textarea id="customer_address" name="customer_address" rows="3" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"><?= htmlspecialchars($customerAddress, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="note">Ghi chú đơn hàng (Tùy chọn):</label>
                    <textarea id="note" name="note" rows="2" maxlength="255" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao..."><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <h2>Phương Thức Thanh Toán</h2>
                <div class="payment-methods">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <span><strong>Thanh toán khi nhận hàng (COD)</strong> - Nhận hàng rồi mới trả tiền</span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="banking">
                        <span><strong>Chuyển khoản ngân hàng qua mã QR</strong></span>
                    </label>
                </div>

                <button type="submit" class="btn-order" <?= $isLoggedIn && !$addresses ? 'disabled' : '' ?>>XÁC NHẬN ĐẶT HÀNG</button>
            </form>
            <a href="cart.php" class="back-link">← Quay lại giỏ hàng kiểm tra</a>
        </div>

        <!-- Cột phải: Xem lại giỏ hàng -->
        <div class="order-summary">
            <h2>Đơn Hàng Của Bạn</h2>
            <ul class="item-list">
                <?php foreach ($orderItems as $item): ?>
                <li class="item">
                    <span><?= (int) $item['quantity'] ?>x <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= $formatCurrency($item['price'] * $item['quantity']) ?></strong>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="price-line">
                <span>Tiền hàng:</span>
                <span><?= $formatCurrency($totalAmount) ?></span>
            </div>
            <div class="price-line">
                <span>Phí vận chuyển:</span>
                <span>0 đ (Miễn phí)</span>
            </div>
            <div class="price-line total-price">
                <span>Tổng cộng:</span>
                <span><?= $formatCurrency($totalAmount) ?></span>
            </div>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const menuPanel = document.getElementById('menuPanel');

        function closeMenu() {
            menuPanel.hidden = true;
            menuToggle.setAttribute('aria-expanded', 'false');
        }

        menuToggle.addEventListener('click', () => {
            const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
            menuPanel.hidden = isOpen;
            menuToggle.setAttribute('aria-expanded', String(!isOpen));
        });
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.site-menu')) closeMenu();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeMenu();
        });
    </script>
</body>
</html>
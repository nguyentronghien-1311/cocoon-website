<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Mỹ Phẩm Thuần Chay Cocoon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2E7D32;    /* Màu xanh lá đậm Cocoon */
            --secondary: #8D6E63;  /* Màu nâu đất mộc mạc */
            --bg: #F9F9F6;         /* Nền be sáng tự nhiên */
            --text: #2c3e50;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 0;
        }
        /* Phần đầu trang */
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
        .container {
            max-width: 950px;
            margin: 35px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        h1 {
            font-size: 22px;
            color: var(--primary);
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
            margin-top: 0;
        }
        /* Bảng danh sách hàng */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            text-align: left;
            padding: 12px;
            background-color: #f4f6f4;
            color: var(--primary);
            font-weight: bold;
        }
        td {
            padding: 15px 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .product-img {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .product-name {
            font-weight: bold;
            color: #333;
        }
        .qty-input {
            width: 55px;
            padding: 6px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .btn-remove {
            background: none;
            border: none;
            color: #d9534f;
            cursor: pointer;
            font-weight: bold;
            text-decoration: underline;
        }
        .empty-cart {
            padding: 25px 20px;
            margin-top: 20px;
            background: #f9f9f9;
            border: 1px dashed #d8d8d8;
            border-radius: 8px;
            text-align: center;
            color: #555;
        }
        .empty-cart p {
            margin: 0 0 15px;
            font-size: 16px;
        }
        /* Phần tính tiền */
        .cart-summary {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }
        .summary-box {
            width: 320px;
            background: #fafafa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .total-row {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary);
            border-top: 1px dashed #ccc;
            padding-top: 12px;
        }
        .btn-checkout {
            display: block;
            width: 100%;
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 14px 0;
            margin-top: 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            box-sizing: border-box;
        }
        .btn-checkout:hover {
            background-color: #1b5e20;
        }
        .continue-shopping {
            display: inline-block;
            margin-top: 20px;
            color: var(--secondary);
            text-decoration: none;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            header { padding: 14px 18px; }
            .tagline { display: none; }
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
        <h1>Giỏ Hàng Của Bạn</h1>

        <div id="cart-empty" class="empty-cart" hidden>
            <p>Giỏ hàng của bạn đang trống.</p>
            <a href="products.php" class="btn-checkout secondary">Tiếp tục mua sắm</a>
        </div>

        <table id="cart-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Tạm tính</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="cart-items"></tbody>
        </table>

        <a href="products.php" class="continue-shopping">← Tiếp tục chọn mua sản phẩm khác</a>

        <div class="cart-summary">
            <div class="summary-box">
                <div class="summary-row">
                    <span>Tạm tính tiền hàng:</span>
                    <span id="subtotal">415.000 đ</span>
                </div>
                <div class="summary-row">
                    <span>Phí giao hàng:</span>
                    <span>Miễn phí</span>
                </div>
                <div class="summary-row total-row">
                    <span>Tổng tiền thanh toán:</span>
                    <span id="grand-total">415.000 đ</span>
                </div>
                <a href="checkout.php" id="checkout-btn" class="btn-checkout">Tiến Hành Thanh Toán</a>
            </div>
        </div>
    </div>

    <script>
        const cartTable = document.getElementById('cart-table');
        const cartEmpty = document.getElementById('cart-empty');
        const subtotalEl = document.getElementById('subtotal');
        const grandTotalEl = document.getElementById('grand-total');
        const checkoutBtn = document.getElementById('checkout-btn');
        const cartItemsEl = document.getElementById('cart-items');
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

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND',
                maximumFractionDigits: 0
            }).format(value).replace('₫', 'đ');
        };

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

        function saveCartItems(items) {
            localStorage.setItem('cocoon_cart_items', JSON.stringify(items));
            const count = items.reduce((total, item) => total + item.quantity, 0);
            localStorage.setItem('cocoon_cart_count', String(count));
        }

        function updateCartTotals(items) {
            const subtotal = items.reduce((total, item) => total + item.price * item.quantity, 0);
            subtotalEl.textContent = formatCurrency(subtotal);
            grandTotalEl.textContent = formatCurrency(subtotal);

            const isEmpty = items.length === 0;
            cartTable.style.display = isEmpty ? 'none' : '';
            cartEmpty.hidden = !isEmpty;

            if (isEmpty) {
                checkoutBtn.setAttribute('href', '#');
                checkoutBtn.setAttribute('aria-disabled', 'true');
                checkoutBtn.style.pointerEvents = 'none';
                checkoutBtn.style.opacity = '0.5';
            } else {
                checkoutBtn.setAttribute('href', 'checkout.php');
                checkoutBtn.removeAttribute('aria-disabled');
                checkoutBtn.style.pointerEvents = 'auto';
                checkoutBtn.style.opacity = '1';
            }
        }

        function renderCart() {
            const items = getCartItems();
            cartItemsEl.replaceChildren();

            items.forEach((item) => {
                const row = document.createElement('tr');
                row.className = 'cart-item';

                const productCell = document.createElement('td');
                const productInfo = document.createElement('div');
                productInfo.className = 'product-info';
                const image = document.createElement('img');
                image.className = 'product-img';
                image.alt = item.name;
                if (item.image.startsWith('data:image/svg+xml')) image.src = item.image;
                const details = document.createElement('div');
                const name = document.createElement('div');
                name.className = 'product-name';
                name.textContent = item.name;
                const category = document.createElement('small');
                category.style.color = '#777';
                category.textContent = item.category;
                details.append(name, category);
                productInfo.append(image, details);
                productCell.append(productInfo);

                const priceCell = document.createElement('td');
                priceCell.textContent = formatCurrency(item.price);
                const quantityCell = document.createElement('td');
                const quantityInput = document.createElement('input');
                quantityInput.type = 'number';
                quantityInput.className = 'qty-input';
                quantityInput.min = '1';
                quantityInput.value = String(item.quantity);
                quantityInput.dataset.productId = String(item.id);
                quantityInput.setAttribute('aria-label', `Số lượng ${item.name}`);
                quantityCell.append(quantityInput);

                const totalCell = document.createElement('td');
                totalCell.className = 'line-total';
                totalCell.textContent = formatCurrency(item.price * item.quantity);
                const actionCell = document.createElement('td');
                const removeButton = document.createElement('button');
                removeButton.className = 'btn-remove';
                removeButton.type = 'button';
                removeButton.dataset.productId = String(item.id);
                removeButton.textContent = 'Xóa';
                actionCell.append(removeButton);
                row.append(productCell, priceCell, quantityCell, totalCell, actionCell);
                cartItemsEl.append(row);
            });

            saveCartItems(items);
            updateCartTotals(items);
        }

        document.addEventListener('change', (event) => {
            if (event.target.matches('.qty-input')) {
                const items = getCartItems();
                const item = items.find((cartItem) => cartItem.id === Number(event.target.dataset.productId));
                if (!item) return;
                item.quantity = Math.max(1, Math.floor(Number(event.target.value) || 1));
                saveCartItems(items);
                renderCart();
            }
        });

        document.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.btn-remove');
            if (removeButton) {
                const items = getCartItems().filter((item) => item.id !== Number(removeButton.dataset.productId));
                saveCartItems(items);
                renderCart();
            }
        });

        window.addEventListener('storage', (event) => {
            if (event.key === 'cocoon_cart_items') renderCart();
        });

        renderCart();
    </script>

</body>
</html>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mã giảm giá đã lưu - Cocoon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7D3F1E;
            --primary-dark: #5a2d15;
            --bg: #f6f1ea;
            --text: #333;
            --muted: #8a7f74;
        }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--text); font-family: Arial, Helvetica, sans-serif; }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; cursor: pointer; }
        .header { padding: 14px 20px; border-bottom: 1px solid #eee; }
        .header-row { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; max-width: 1160px; margin: 0 auto; }
        .logo { grid-column: 2; grid-row: 1; display: flex; flex-direction: column; align-items: center; color: #3E3228; font-weight: 500; }
        .menu-wrap { grid-column: 3; grid-row: 1; justify-self: end; }
        .logo-the, .logo-name { font-family: "Cormorant Garamond", Georgia, serif; font-style: italic; }
        .logo-the { margin-bottom: -4px; font-size: 13px; line-height: 1; }
        .logo-name { font-size: 28px; line-height: 0.9; }
        .logo-country { margin-top: 4px; color: #8D6E63; font-size: 8px; letter-spacing: 0.38em; }
        .menu-wrap { position: relative; }
        .menu-toggle { display: grid; gap: 5px; padding: 10px; border: 1px solid #e8e2d9; border-radius: 5px; background: #fff; }
        .menu-toggle span { width: 20px; height: 2px; background: var(--primary); }
        .menu-panel { position: absolute; top: calc(100% + 8px); right: 0; z-index: 2; min-width: 200px; padding: 6px; border: 1px solid #e8e2d9; background: #fff; box-shadow: 0 8px 22px rgba(0,0,0,.12); }
        .menu-panel[hidden] { display: none; }
        .menu-panel a { display: block; padding: 10px 12px; font-size: 14px; }
        .menu-panel a:hover { background: var(--bg); color: var(--primary); }
        main { width: min(100% - 32px, 960px); margin: 42px auto; }
        h1 { margin: 0; color: var(--primary); font-size: 28px; }
        .intro { margin: 8px 0 24px; color: var(--muted); }
        .coupon-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .coupon-card { padding: 20px; border: 1px solid #e8e2d9; border-radius: 8px; background: #fff; }
        .coupon-card h2 { margin: 0 0 8px; color: var(--primary); font-size: 20px; }
        .coupon-card p { margin: 0 0 14px; }
        .coupon-code { display: block; margin-bottom: 14px; color: #b5471f; font-weight: 700; letter-spacing: .4px; user-select: all; }
        .coupon-actions { display: flex; align-items: center; gap: 12px; }
        .coupon-actions button { padding: 8px 12px; border: 0; border-radius: 4px; background: var(--primary); color: #fff; }
        .coupon-actions button:hover { background: var(--primary-dark); }
        .coupon-actions .remove { background: transparent; color: var(--muted); text-decoration: underline; }
        .coupon-actions .remove:hover { background: transparent; color: var(--primary); }
        .message { padding: 20px; border-radius: 8px; background: var(--bg); color: var(--muted); }
        .message a { color: var(--primary); font-weight: 700; text-decoration: underline; }
        @media (max-width: 600px) {
            .coupon-list { grid-template-columns: 1fr; }
            main { margin-top: 30px; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-row">
            <a class="logo" href="index.php" aria-label="the cocoon Vietnam - Trang chủ">
                <span class="logo-the">the</span>
                <span class="logo-name">cocoon</span>
                <span class="logo-country">VIETNAM</span>
            </a>
            <div class="menu-wrap">
                <button class="menu-toggle" id="menuToggle" type="button" aria-label="Mở menu" aria-controls="menuPanel" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
                <nav class="menu-panel" id="menuPanel" aria-label="Menu chính" hidden>
                    <a href="index.php">Trang chủ</a>
                    <a href="products.php">Sản phẩm</a>
                    <a href="cart.php">Giỏ hàng</a>
                    <a href="saved-coupons.php" aria-current="page">Mã giảm giá đã lưu</a>
                </nav>
            </div>
        </div>
    </header>
    <main>
        <h1>Mã giảm giá đã lưu</h1>
        <p class="intro">Các mã được lưu khi bạn chọn “Sao chép mã” trên trang chủ.</p>
        <div class="coupon-list" id="couponList" aria-live="polite"></div>
    </main>
    <script>
        const savedCouponsKey = 'cocoon_saved_coupons';
        const couponList = document.getElementById('couponList');
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
        document.addEventListener('click', event => {
            if (!event.target.closest('.menu-wrap')) closeMenu();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeMenu();
        });

        function loadSavedCoupons() {
            const stored = localStorage.getItem(savedCouponsKey);
            if (stored === null) return [];
            const coupons = JSON.parse(stored);
            if (!Array.isArray(coupons) || !coupons.every(coupon =>
                coupon && ['code', 'big', 'min', 'txt'].every(key => typeof coupon[key] === 'string')
            )) {
                throw new Error('Danh sách mã giảm giá đã lưu không hợp lệ.');
            }
            return coupons;
        }

        function renderCoupons() {
            couponList.replaceChildren();
            let coupons;
            try {
                coupons = loadSavedCoupons();
            } catch (error) {
                console.error('Không thể đọc mã giảm giá đã lưu.', error);
                const message = document.createElement('p');
                message.className = 'message';
                message.textContent = 'Không thể đọc danh sách mã giảm giá đã lưu trên thiết bị này.';
                couponList.append(message);
                return;
            }

            if (coupons.length === 0) {
                const message = document.createElement('p');
                message.className = 'message';
                message.append('Bạn chưa lưu mã giảm giá nào. Hãy xem các ưu đãi trên ');
                const homeLink = document.createElement('a');
                homeLink.href = 'index.php#coupons';
                homeLink.textContent = 'trang chủ';
                message.append(homeLink, '.');
                couponList.append(message);
                return;
            }

            coupons.forEach(coupon => {
                const card = document.createElement('article');
                card.className = 'coupon-card';
                const title = document.createElement('h2');
                title.textContent = `Giảm ${coupon.big}`;
                const minimum = document.createElement('p');
                minimum.textContent = `Đơn hàng từ ${coupon.min}`;
                const description = document.createElement('p');
                description.textContent = coupon.txt;
                const code = document.createElement('code');
                code.className = 'coupon-code';
                code.textContent = coupon.code;
                const actions = document.createElement('div');
                actions.className = 'coupon-actions';
                const copyButton = document.createElement('button');
                copyButton.type = 'button';
                copyButton.textContent = 'Sao chép mã';
                copyButton.addEventListener('click', async () => {
                    try {
                        if (!navigator.clipboard) throw new Error('Clipboard API không khả dụng.');
                        await navigator.clipboard.writeText(coupon.code);
                        copyButton.textContent = 'Đã chép!';
                    } catch (error) {
                        console.error('Không thể sao chép mã giảm giá.', error);
                        copyButton.textContent = 'Không thể sao chép';
                    }
                    setTimeout(() => copyButton.textContent = 'Sao chép mã', 1500);
                });
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'remove';
                removeButton.textContent = 'Xóa';
                removeButton.addEventListener('click', () => {
                    try {
                        const remainingCoupons = loadSavedCoupons().filter(item => item.code !== coupon.code);
                        localStorage.setItem(savedCouponsKey, JSON.stringify(remainingCoupons));
                        renderCoupons();
                    } catch (error) {
                        console.error('Không thể xóa mã giảm giá đã lưu.', error);
                        window.alert('Không thể cập nhật danh sách mã giảm giá trên thiết bị này.');
                    }
                });
                actions.append(copyButton, removeButton);
                card.append(title, minimum, description, code, actions);
                couponList.append(card);
            });
        }

        renderCoupons();
    </script>
</body>
</html>

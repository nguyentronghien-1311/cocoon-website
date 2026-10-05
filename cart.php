<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Mỹ Phẩm Thuần Chay Cocoon</title>
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
            background: white;
            border-bottom: 2px solid #e0e0e0;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
            color: var(--primary);
            text-decoration: none;
        }
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
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo">COCOON</a>
        <span>Mỹ phẩm thuần chay 100% Việt Nam</span>
    </header>

    <div class="container">
        <h1>Giỏ Hàng Của Bạn</h1>

        <div id="cart-empty" class="empty-cart" hidden>
            <p>Giỏ hàng của bạn đang trống.</p>
            <a href="product.php" class="btn-checkout secondary">Tiếp tục mua sắm</a>
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
            <tbody id="cart-items">
                <tr class="cart-item" data-price="125000">
                    <td>
                        <div class="product-info">
                            <img class="product-img" src="https://image.hsv-tech.io/600x600/bbx/common/5d4bf9c3-1fb3-488d-a417-062e2ffdb20c.webp" alt="Tẩy da chết">
                            <div>
                                <div class="product-name">Cà phê Đắk Lắk làm sạch da chết cơ thể</div>
                                <small style="color: #777;">Dung tích: 200ml</small>
                            </div>
                        </div>
                    </td>
                    <td class="unit-price">125.000 đ</td>
                    <td><input type="number" class="qty-input" value="1" min="1"></td>
                    <td class="line-total"><strong>125.000 đ</strong></td>
                    <td><button class="btn-remove" type="button">Xóa</button></td>
                </tr>
                <tr class="cart-item" data-price="145000">
                    <td>
                        <div class="product-info">
                            <img class="product-img" src="https://image.hsv-tech.io/600x600/bbx/common/a60e0a54-7f1b-4395-8123-5e921d743a41.webp" alt="Nước dưỡng tóc">
                            <div>
                                <div class="product-name">Nước dưỡng tóc tinh dầu bưởi Cocoon</div>
                                <small style="color: #777;">Dung tích: 140ml</small>
                            </div>
                        </div>
                    </td>
                    <td class="unit-price">145.000 đ</td>
                    <td><input type="number" class="qty-input" value="2" min="1"></td>
                    <td class="line-total"><strong>290.000 đ</strong></td>
                    <td><button class="btn-remove" type="button">Xóa</button></td>
                </tr>
            </tbody>
        </table>

        <a href="product.php" class="continue-shopping">← Tiếp tục chọn mua sản phẩm khác</a>

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

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND',
                maximumFractionDigits: 0
            }).format(value).replace('₫', 'đ');
        };

        function updateCartTotals() {
            const rows = document.querySelectorAll('.cart-item');
            let subtotal = 0;

            rows.forEach(row => {
                const qtyInput = row.querySelector('.qty-input');
                const price = Number(row.dataset.price) || 0;
                const qty = Math.max(1, Number(qtyInput.value) || 1);
                qtyInput.value = qty;

                const lineTotal = price * qty;
                row.querySelector('.line-total').innerHTML = `<strong>${formatCurrency(lineTotal)}</strong>`;
                subtotal += lineTotal;
            });

            subtotalEl.textContent = formatCurrency(subtotal);
            grandTotalEl.textContent = formatCurrency(subtotal);

            const isEmpty = rows.length === 0;
            cartTable.style.display = isEmpty ? 'none' : '';
            cartEmpty.hidden = !isEmpty;

            if (isEmpty) {
                checkoutBtn.setAttribute('href', '#');
                checkoutBtn.style.pointerEvents = 'none';
                checkoutBtn.style.opacity = '0.5';
            } else {
                checkoutBtn.setAttribute('href', 'checkout.php');
                checkoutBtn.style.pointerEvents = 'auto';
                checkoutBtn.style.opacity = '1';
            }
        }

        document.addEventListener('input', function (event) {
            if (event.target.matches('.qty-input')) {
                updateCartTotals();
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target.matches('.btn-remove')) {
                const row = event.target.closest('.cart-item');
                if (row) {
                    row.remove();
                    updateCartTotals();
                }
            }
        });

        updateCartTotals();
    </script>

</body>
</html>
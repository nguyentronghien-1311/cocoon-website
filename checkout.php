<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - Mỹ Phẩm Thuần Chay Cocoon</title>
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
        input:focus, textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
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
        <a href="index.php" class="logo">COCOON</a>
        <span>Mỹ phẩm thuần chay 100% Việt Nam</span>
    </header>

    <div class="container">
        <!-- Cột trái: Thông tin nhận hàng -->
        <div class="checkout-form">
            <h2>Thông Tin Giao Hàng</h2>
            <form action="#" method="POST" onsubmit="alert('Đặt hàng thành công! Đơn hàng đã được gửi đến hệ thống.'); return false;">
                <div class="form-group">
                    <label>Họ và tên người nhận (*):</label>
                    <input type="text" name="customer_name" required placeholder="Ví dụ: Nguyễn Văn A">
                </div>

                <div class="form-group">
                    <label>Số điện thoại (*):</label>
                    <input type="tel" name="customer_phone" required placeholder="Ví dụ: 0987654321">
                </div>

                <div class="form-group">
                    <label>Địa chỉ nhận hàng chi tiết (*):</label>
                    <textarea name="customer_address" rows="3" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"></textarea>
                </div>

                <div class="form-group">
                    <label>Ghi chú đơn hàng (Tùy chọn):</label>
                    <textarea name="note" rows="2" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao..."></textarea>
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

                <button type="submit" class="btn-order">XÁC NHẬN ĐẶT HÀNG</button>
            </form>
            <a href="cart.php" class="back-link">← Quay lại giỏ hàng kiểm tra</a>
        </div>

        <!-- Cột phải: Xem lại giỏ hàng -->
        <div class="order-summary">
            <h2>Đơn Hàng Của Bạn</h2>
            <ul class="item-list">
                <li class="item">
                    <span>1x Cà phê Đắk Lắk làm sạch da chết</span>
                    <strong>125.000 đ</strong>
                </li>
                <li class="item">
                    <span>2x Nước dưỡng tóc tinh dầu bưởi</span>
                    <strong>290.000 đ</strong>
                </li>
            </ul>

            <div class="price-line">
                <span>Tiền hàng:</span>
                <span>415.000 đ</span>
            </div>
            <div class="price-line">
                <span>Phí vận chuyển:</span>
                <span>0 đ (Miễn phí)</span>
            </div>
            <div class="price-line total-price">
                <span>Tổng cộng:</span>
                <span>415.000 đ</span>
            </div>
        </div>
    </div>

</body>
</html>
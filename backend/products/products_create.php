```php
<?php
// File: backend/products/products_create.php
// Trang thêm sản phẩm mới cho cơ sở dữ liệu cocoon_db

require_once __DIR__ . '/../connect.php';

if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối MySQLi trong backend/connect.php.');
}

// Lấy danh sách danh mục
$sql = "SELECT id, name FROM categories ORDER BY id DESC";
$result = $connect->query($sql);

if (!$result) {
    die('Lỗi truy vấn danh mục: ' .
        htmlspecialchars($connect->error, ENT_QUOTES, 'UTF-8'));
}

$categories = $result->fetch_all(MYSQLI_ASSOC);

function e($value): string {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Thêm sản phẩm mới</title>

    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet"
          href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">

    <style>
        .product-form {
            max-width: 1000px;
        }

        .product-form .form-group {
            margin-bottom: 18px;
        }

        .required {
            color: red;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }

        .form-actions .btn {
            min-width: 110px;
        }

        .btn-cancel {
            background-color: #6c757d;
            color: #fff;
        }

        .btn-cancel:hover {
            background-color: #565e64;
            color: #fff;
        }
    </style>
</head>

<body onload="time()" class="app sidebar-mini rtl">

    <!-- Navbar -->
    <header class="app-header">
        <a class="app-sidebar__toggle"
           href="#"
           data-toggle="sidebar"
           aria-label="Ẩn thanh bên"></a>

        <ul class="app-nav">
            <li>
                <a class="app-nav__item" href="/index.php">
                    <i class="bx bx-log-out bx-rotate-180"></i>
                </a>
            </li>
        </ul>
    </header>

    <!-- Sidebar overlay -->
    <div class="app-sidebar__overlay" data-toggle="sidebar"></div>

    <!-- Sidebar -->
    <aside class="app-sidebar">

        <div class="app-sidebar__user">
            <img class="app-sidebar__user-avatar"
                 src="../img/cover-2.png"
                 width="50"
                 alt="User Image">

            <div>
                <p class="app-sidebar__user-name"><b>Admin</b></p>
                <p class="app-sidebar__user-designation">
                    Chào mừng bạn trở lại
                </p>
            </div>
        </div>

        <hr>

        <ul class="app-menu">
            <li>
                <a class="app-menu__item"
                   href="../dashboard.php">
                    <i class="app-menu__icon bx bx-tachometer"></i>
                    <span class="app-menu__label">Tổng quan Admin</span>
                </a>
            </li>

            <li>
                <a class="app-menu__item"
                   href="../categories/admin_cats.php">
                    <i class="app-menu__icon bx bx-folder"></i>
                    <span class="app-menu__label">Quản lý danh mục</span>
                </a>
            </li>

            <li>
                <a class="app-menu__item active"
                   href="admin_pros.php">
                    <i class="app-menu__icon bx bx-box"></i>
                    <span class="app-menu__label">Quản lý sản phẩm</span>
                </a>
            </li>

            <li>
                <a class="app-menu__item"
                   href="../orders/admin_orders.php">
                    <i class="app-menu__icon bx bx-shopping-bag"></i>
                    <span class="app-menu__label">Quản lý đơn hàng</span>
                </a>
            </li>
        </ul>

    </aside>

    <!-- Main content -->
    <main class="app-content">

        <div class="app-title">
            <ul class="app-breadcrumb breadcrumb">
                <li class="breadcrumb-item">
                    <a href="admin_pros.php">Danh sách sản phẩm</a>
                </li>

                <li class="breadcrumb-item active">
                    <b>Thêm sản phẩm mới</b>
                </li>
            </ul>

            <div id="clock"></div>
        </div>

        <div class="row">
            <div class="col-md-12">

                <div class="tile product-form">

                    <h3 class="tile-title">Thông tin sản phẩm</h3>

                    <div class="tile-body">

                        <form method="POST"
                              action="products_create_process.php"
                              enctype="multipart/form-data">

                            <!-- Tên sản phẩm -->
                            <div class="form-group">
                                <label for="name">
                                    Tên sản phẩm
                                    <span class="required">*</span>
                                </label>

                                <input
                                    class="form-control"
                                    type="text"
                                    id="name"
                                    name="name"
                                    maxlength="255"
                                    placeholder="Nhập tên sản phẩm"
                                    required>
                            </div>

                            <div class="row">

                                <!-- Giá tiền -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="price">
                                            Giá tiền (VNĐ)
                                            <span class="required">*</span>
                                        </label>

                                        <input
                                            class="form-control"
                                            type="number"
                                            id="price"
                                            name="price"
                                            min="0"
                                            step="1"
                                            placeholder="Nhập giá sản phẩm"
                                            required>
                                    </div>
                                </div>

                                <!-- Số lượng -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="stock">
                                            Số lượng tồn kho
                                            <span class="required">*</span>
                                        </label>

                                        <input
                                            class="form-control"
                                            type="number"
                                            id="stock"
                                            name="stock"
                                            min="0"
                                            step="1"
                                            value="0"
                                            required>
                                    </div>
                                </div>

                            </div>

                            <!-- Danh mục -->
                            <div class="form-group">
                                <label for="category_id">
                                    Danh mục
                                    <span class="required">*</span>
                                </label>

                                <select
                                    class="form-control"
                                    id="category_id"
                                    name="category_id"
                                    required>

                                    <option value="">
                                        -- Chọn danh mục --
                                    </option>

                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= e($category['id']) ?>">
                                            <?= e($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <!-- Volume -->
                            <div class="form-group">
                                <label for="volume">Kích cỡ / Dung tích</label>

                                <input
                                    class="form-control"
                                    type="text"
                                    id="volume"
                                    name="volume"
                                    maxlength="100"
                                    placeholder="Ví dụ: S, M, L hoặc 100ml">
                            </div>

                            <!-- Mô tả -->
                            <div class="form-group">
                                <label for="description">Mô tả sản phẩm</label>

                                <textarea
                                    class="form-control"
                                    id="description"
                                    name="description"
                                    rows="5"
                                    placeholder="Nhập mô tả sản phẩm..."></textarea>
                            </div>

                            <!-- Hình ảnh -->
                            <div class="form-group">
                                <label for="image">
                                    Hình ảnh sản phẩm
                                    <span class="required">*</span>
                                </label>

                                <input
                                    class="form-control"
                                    type="file"
                                    id="image"
                                    name="image"
                                    accept=".jpg,.jpeg,.png,.webp,.gif"
                                    required>

                                <small class="form-text text-muted">
                                    Định dạng JPG, JPEG, PNG, WEBP hoặc GIF.
                                </small>
                            </div>

                            <!-- Sản phẩm nổi bật -->
                            <div class="form-group">
                                <label for="is_featured">
                                    Sản phẩm nổi bật
                                </label>

                                <select
                                    class="form-control"
                                    id="is_featured"
                                    name="is_featured">

                                    <option value="0" selected>Không</option>
                                    <option value="1">Có</option>

                                </select>
                            </div>

                            <!-- Nút thao tác -->
                            <div class="form-actions">

                                <a href="admin_pros.php"
                                   class="btn btn-cancel">
                                    <i class="fas fa-times"></i>
                                    Hủy bỏ
                                </a>

                                <button
                                    type="submit"
                                    name="create_product"
                                    class="btn btn-primary">

                                    <i class="fas fa-save"></i>
                                    Lưu sản phẩm

                                </button>

                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>

    </main>

    <!-- JavaScript -->
    <script src="../js/jquery-3.2.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/main.js"></script>

    <script>
        function time() {
            const today = new Date();

            const weekday = [
                "Chủ Nhật",
                "Thứ Hai",
                "Thứ Ba",
                "Thứ Tư",
                "Thứ Năm",
                "Thứ Sáu",
                "Thứ Bảy"
            ];

            const day = weekday[today.getDay()];
            const dd = String(today.getDate()).padStart(2, "0");
            const mm = String(today.getMonth() + 1).padStart(2, "0");
            const yyyy = today.getFullYear();

            const h = String(today.getHours()).padStart(2, "0");
            const m = String(today.getMinutes()).padStart(2, "0");
            const s = String(today.getSeconds()).padStart(2, "0");

            document.getElementById("clock").innerHTML =
                '<span class="date">' +
                day + ', ' + dd + '/' + mm + '/' + yyyy +
                ' - ' + h + ' giờ ' + m + ' phút ' + s + ' giây' +
                '</span>';

            setTimeout(time, 1000);
        }
    </script>

</body>
</html>
```
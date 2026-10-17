<?php
require_once __DIR__ . '/../admin_guard.php'; // chỉ admin được vào
// File: backend/products/products_create.php

require_once __DIR__ . '/../connect.php';

if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối MySQLi.');
}

function e($value): string {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function post_text(string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

// Lấy danh sách danh mục
$sql = "SELECT id, name FROM categories ORDER BY id DESC";
$result = $connect->query($sql);

if (!$result) {
    die('Lỗi truy vấn danh mục: ' .
        htmlspecialchars($connect->error, ENT_QUOTES, 'UTF-8'));
}

$categories = $result->fetch_all(MYSQLI_ASSOC);

// Xử lý khi bấm Lưu sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = post_text('name');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $volume = post_text('volume');
    $description = post_text('description');
    $ingredients = post_text('ingredients');
    $usage_guide = post_text('usage_guide');
    $is_featured = $_POST['is_featured'] ?? '0';

    
$errors = [];

// Kiểm tra tên sản phẩm
if ($name === '') {
    $errors[] = 'Tên sản phẩm đang trống.';
}
if (mb_strlen($volume, 'UTF-8') > 50) {
    $errors[] = 'Kích cỡ / dung tích không được vượt quá 50 ký tự.';
}

// Kiểm tra giá
if ($price === '' || !is_numeric($price) || (float)$price < 0) {
    $errors[] = 'Giá sản phẩm phải là số không âm.';
}

// Kiểm tra số lượng tồn kho
$stockInt = filter_var($stock, FILTER_VALIDATE_INT);

if ($stockInt === false || $stockInt < 0) {
    $errors[] = 'Số lượng tồn kho phải là số nguyên không âm.';
}

// Kiểm tra danh mục
$categoryInt = filter_var($category_id, FILTER_VALIDATE_INT);

if ($categoryInt === false || $categoryInt <= 0) {
    $errors[] = 'Bạn chưa chọn danh mục hợp lệ.';
}

// Kiểm tra sản phẩm nổi bật
if (!in_array((string)$is_featured, ['0', '1'], true)) {
    $errors[] = 'Giá trị sản phẩm nổi bật không hợp lệ.';
}

if (!empty($errors)) {
    $message = implode("\\n", $errors);
    echo '<script>alert(' . json_encode(
        $message,
        JSON_UNESCAPED_UNICODE
    ) . ');</script>';
} else {


        $price = (float)$price;
        $stock = (int)$stock;
        $category_id = (int)$category_id;
        $is_featured = (int)$is_featured;

        // Kiểm tra danh mục tồn tại
        $check = $connect->prepare(
            "SELECT id FROM categories WHERE id = ?"
        );
        $check->bind_param("i", $category_id);
        $check->execute();
        $categoryExists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$categoryExists) {
            echo "<script>alert('Danh mục không tồn tại!');</script>";
        } elseif (
            !isset($_FILES['image']) ||
            $_FILES['image']['error'] !== UPLOAD_ERR_OK
        ) {
            echo "<script>alert('Vui lòng chọn ảnh hợp lệ!');</script>";
        } else {

            $file = $_FILES['image'];
            $maxSize = 5 * 1024 * 1024; // 5 MB

            // Kiểm tra dung lượng
            if ($file['size'] > $maxSize) {
                echo "<script>alert('Ảnh không được vượt quá 5 MB!');</script>";
            } else {

                // Kiểm tra file thực sự là ảnh
                $imageInfo = @getimagesize($file['tmp_name']);

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif'
                ];

                $mime = $imageInfo['mime'] ?? '';

                if (!$imageInfo || !isset($allowedTypes[$mime])) {
                    echo "<script>alert('Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF!');</script>";
                } else {

                    // Thư mục: cocoon-website/assets/images
                    $imageDirectory = dirname(__DIR__, 2)
                        . DIRECTORY_SEPARATOR . 'assets'
                        . DIRECTORY_SEPARATOR . 'images';

                    if (
                        !is_dir($imageDirectory) &&
                        !mkdir($imageDirectory, 0755, true)
                    ) {
                        echo "<script>alert('Không thể tạo thư mục images!');</script>";
                    } else {

                        // Tạo tên ảnh duy nhất
                        $extension = $allowedTypes[$mime];
                        $imageName = 'product_'
                            . bin2hex(random_bytes(8))
                            . '.' . $extension;

                        $imagePath = $imageDirectory
                            . DIRECTORY_SEPARATOR . $imageName;

                        // Lưu ảnh vào thư mục
                        if (!move_uploaded_file(
                            $file['tmp_name'],
                            $imagePath
                        )) {
                            echo "<script>alert('Lưu ảnh thất bại!');</script>";
                        } else {

                            // Lưu thông tin sản phẩm vào database
                            $insert = $connect->prepare(
                                "INSERT INTO products
                                (name, price, image, description, ingredients,
                                 usage_guide, stock, category_id, volume, is_featured)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                            );

                            if (!$insert) {
                                if (is_file($imagePath)) {
                                    unlink($imagePath);
                                }

                                echo "<script>alert('Không thể chuẩn bị câu lệnh SQL!');</script>";
                            } else {

                                $insert->bind_param(
                                    "sdssssiisi",
                                    $name,
                                    $price,
                                    $imageName,
                                    $description,
                                    $ingredients,
                                    $usage_guide,
                                    $stock,
                                    $category_id,
                                    $volume,
                                    $is_featured
                                );

                                if ($insert->execute()) {
                                    $insert->close();

                                    echo "<script>
                                        alert('Thêm sản phẩm thành công!');
                                        window.location.href = 'admin_pros.php';
                                    </script>";
                                    exit;
                                } else {
                                    $insert->close();

                                    // Xóa ảnh nếu lưu database thất bại
                                    if (is_file($imagePath)) {
                                        unlink($imagePath);
                                    }

                                    echo "<script>alert('Thêm sản phẩm thất bại!');</script>";
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Thêm sản phẩm mới</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
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
                 src="../images/cover-2.png"
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
                    <i class="app-menu__icon bx bx-purchase-tag-alt"></i>
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
                   href="../orders/admin_ords.php">
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
                            action="products_create.php"
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
                                    maxlength="50"
                                    placeholder="Ví dụ: S, M, L hoặc 100ml">
                            </div>

                            <div class="form-group">
                                <label for="ingredients">Thành phần</label>
                                <textarea
                                    class="form-control"
                                    id="ingredients"
                                    name="ingredients"
                                    rows="4"
                                    placeholder="Nhập các thành phần, phân tách bằng dấu phẩy..."><?= e(post_text('ingredients')) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="usage_guide">Hướng dẫn sử dụng</label>
                                <textarea
                                    class="form-control"
                                    id="usage_guide"
                                    name="usage_guide"
                                    rows="4"
                                    placeholder="Nhập hướng dẫn sử dụng..."><?= e(post_text('usage_guide')) ?></textarea>
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

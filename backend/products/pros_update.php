<?php
require_once __DIR__ . '/../admin_guard.php'; // chỉ admin được vào
// File: backend/products/products_edit.php

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

// Kiểm tra ID sản phẩm từ URL
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id || $product_id <= 0) {
    die('Mã sản phẩm không hợp lệ!');
}

// Lấy thông tin sản phẩm
$stmt = $connect->prepare(
    "SELECT id, name, price, image, description, ingredients,
            usage_guide, stock, category_id, volume, is_featured
     FROM products
     WHERE id = ?"
);

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    die('Không tìm thấy sản phẩm!');
}

// Lấy danh sách danh mục
$result = $connect->query(
    "SELECT id, name FROM categories ORDER BY id DESC"
);

if (!$result) {
    die('Lỗi truy vấn danh mục: ' . e($connect->error));
}

$categories = $result->fetch_all(MYSQLI_ASSOC);

// Biến lưu thông báo
$error = '';
$success = false;

// Xử lý cập nhật sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $priceInput = $_POST['price'] ?? '';
    $stockInput = $_POST['stock'] ?? '';
    $categoryInput = $_POST['category_id'] ?? '';
    $volume = post_text('volume');
    $description = post_text('description');
    $ingredients = post_text('ingredients');
    $usage_guide = post_text('usage_guide');
    $featuredInput = $_POST['is_featured'] ?? '';

    // Giữ lại dữ liệu vừa nhập nếu có lỗi
    $product['name'] = $name;
    $product['price'] = $priceInput;
    $product['stock'] = $stockInput;
    $product['category_id'] = $categoryInput;
    $product['volume'] = $volume;
    $product['description'] = $description;
    $product['ingredients'] = $ingredients;
    $product['usage_guide'] = $usage_guide;
    $product['is_featured'] = $featuredInput;

    // Kiểm tra dữ liệu đầu vào
    if (
        $name === '' ||
        mb_strlen($name, 'UTF-8') > 255 ||
        $priceInput === '' ||
        !is_numeric($priceInput) ||
        !is_finite((float)$priceInput) ||
        (float)$priceInput < 0 ||
        filter_var($stockInput, FILTER_VALIDATE_INT) === false ||
        (int)$stockInput < 0 ||
        filter_var($categoryInput, FILTER_VALIDATE_INT) === false ||
        (int)$categoryInput <= 0 ||
        !in_array((string)$featuredInput, ['0', '1'], true)
    ) {
        $error = 'Dữ liệu sản phẩm không hợp lệ!';
    } elseif (mb_strlen($volume, 'UTF-8') > 50) {
        $error = 'Kích cỡ / dung tích không được vượt quá 50 ký tự!';
    } else {

        $price = (float)$priceInput;
        $stock = (int)$stockInput;
        $category_id = (int)$categoryInput;
        $is_featured = (int)$featuredInput;

        // Kiểm tra danh mục có tồn tại hay không
        $check = $connect->prepare(
            "SELECT id FROM categories WHERE id = ?"
        );

        $check->bind_param("i", $category_id);
        $check->execute();

        $categoryExists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$categoryExists) {
            $error = 'Danh mục không tồn tại!';
        } else {

            // Mặc định giữ ảnh hiện tại
            $imageName = $product['image'];
            $newImagePath = null;

            // Chỉ xử lý ảnh nếu người dùng chọn ảnh mới
            $hasNewImage = isset($_FILES['image'])
                && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;

            if ($hasNewImage) {

                $file = $_FILES['image'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Có lỗi khi tải ảnh lên!';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $error = 'Ảnh không được vượt quá 5 MB!';
                } else {

                    $imageInfo = @getimagesize($file['tmp_name']);

                    $allowedTypes = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                        'image/gif'  => 'gif'
                    ];

                    $mime = $imageInfo['mime'] ?? '';

                    if (!$imageInfo || !isset($allowedTypes[$mime])) {
                        $error = 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF!';
                    } else {

                        // Thư mục cocoon-website/assets/images
                        $imageDirectory = dirname(__DIR__, 2)
                            . DIRECTORY_SEPARATOR . 'assets'
                            . DIRECTORY_SEPARATOR . 'images';

                        if (
                            !is_dir($imageDirectory) &&
                            !mkdir($imageDirectory, 0755, true)
                        ) {
                            $error = 'Không thể tạo thư mục images!';
                        } else {

                            $extension = $allowedTypes[$mime];

                            $imageName = 'product_'
                                . bin2hex(random_bytes(8))
                                . '.' . $extension;

                            $newImagePath = $imageDirectory
                                . DIRECTORY_SEPARATOR . $imageName;

                            if (!move_uploaded_file(
                                $file['tmp_name'],
                                $newImagePath
                            )) {
                                $error = 'Lưu ảnh mới thất bại!';
                                $newImagePath = null;
                            }
                        }
                    }
                }
            }

            // Cập nhật database nếu dữ liệu hợp lệ
            if ($error === '') {

                $update = $connect->prepare(
                    "UPDATE products
                     SET name = ?,
                         price = ?,
                         image = ?,
                         description = ?,
                         ingredients = ?,
                         usage_guide = ?,
                         stock = ?,
                         category_id = ?,
                         volume = ?,
                         is_featured = ?
                     WHERE id = ?"
                );

                if (!$update) {
                    $error = 'Không thể chuẩn bị câu lệnh cập nhật!';
                } else {

                    $update->bind_param(
                        "sdssssiisii",
                        $name,
                        $price,
                        $imageName,
                        $description,
                        $ingredients,
                        $usage_guide,
                        $stock,
                        $category_id,
                        $volume,
                        $is_featured,
                        $product_id
                    );

                    if ($update->execute()) {

                        $update->close();

                        // Xóa ảnh cũ sau khi cập nhật thành công
                        if (
                            $hasNewImage &&
                            $newImagePath !== null &&
                            !empty($product['image'])
                        ) {
                            $oldImageName = basename($product['image']);

                            $oldImagePath = dirname(__DIR__, 2)
                                . DIRECTORY_SEPARATOR . 'assets'
                                . DIRECTORY_SEPARATOR . 'images'
                                . DIRECTORY_SEPARATOR . $oldImageName;

                            if (
                                is_file($oldImagePath) &&
                                realpath($oldImagePath) !==
                                    realpath($newImagePath)
                            ) {
                                @unlink($oldImagePath);
                            }
                        }

                        echo "<script>
                            alert('Cập nhật sản phẩm thành công!');
                            window.location.href = 'admin_pros.php';
                        </script>";
                        exit;

                    } else {

                        $update->close();

                        // Xóa ảnh mới nếu cập nhật database thất bại
                        if (
                            $newImagePath !== null &&
                            is_file($newImagePath)
                        ) {
                            unlink($newImagePath);
                        }

                        $error = 'Cập nhật sản phẩm thất bại!';
                    }
                }

                // Dọn ảnh mới nếu câu lệnh SQL không tạo được
                if (
                    $error !== '' &&
                    $newImagePath !== null &&
                    is_file($newImagePath)
                ) {
                    unlink($newImagePath);
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

    <title>Chỉnh sửa sản phẩm</title>

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
            min-width: 120px;
        }

        .btn-cancel {
            background-color: #6c757d;
            color: #fff;
        }

        .btn-cancel:hover {
            background-color: #565e64;
            color: #fff;
        }

        .current-image {
            margin-top: 10px;
        }

        .current-image img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
            background: #fff;
        }

        .current-image p {
            margin-top: 8px;
            margin-bottom: 0;
            color: #666;
        }

        .error-message {
            padding: 12px 15px;
            margin-bottom: 20px;
            color: #842029;
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            border-radius: 4px;
        }

        @media (max-width: 576px) {
            .form-actions {
                flex-wrap: wrap;
            }

            .form-actions .btn {
                flex: 1;
            }
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
                <a class="app-menu__item haha" href="../dashboard.php">
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
                    <b>Chỉnh sửa sản phẩm</b>
                </li>
            </ul>

            <div id="clock"></div>
        </div>

        <div class="row">
            <div class="col-md-12">

                <div class="tile product-form">

                    <h3 class="tile-title">Chỉnh sửa thông tin sản phẩm</h3>

                    <div class="tile-body">

                        <?php if ($error !== ''): ?>
                            <div class="error-message" role="alert">
                                <?= e($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">

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
                                    value="<?= e($product['name']) ?>"
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
                                            value="<?= e($product['price']) ?>"
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
                                            value="<?= e($product['stock']) ?>"
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
                                        <option
                                            value="<?= e($category['id']) ?>"
                                            <?= (string)$product['category_id']
                                                === (string)$category['id']
                                                ? 'selected' : '' ?>>

                                            <?= e($category['name']) ?>

                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <!-- Kích cỡ / Dung tích -->
                            <div class="form-group">
                                <label for="volume">
                                    Kích cỡ / Dung tích
                                </label>

                                <input
                                    class="form-control"
                                    type="text"
                                    id="volume"
                                    name="volume"
                                    maxlength="50"
                                    placeholder="Ví dụ: S, M, L hoặc 100ml"
                                    value="<?= e($product['volume']) ?>">
                            </div>

                            <div class="form-group">
                                <label for="ingredients">Thành phần</label>
                                <textarea
                                    class="form-control"
                                    id="ingredients"
                                    name="ingredients"
                                    rows="4"
                                    placeholder="Nhập các thành phần, phân tách bằng dấu phẩy..."><?= e($product['ingredients']) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="usage_guide">Hướng dẫn sử dụng</label>
                                <textarea
                                    class="form-control"
                                    id="usage_guide"
                                    name="usage_guide"
                                    rows="4"
                                    placeholder="Nhập hướng dẫn sử dụng..."><?= e($product['usage_guide']) ?></textarea>
                            </div>

                            <!-- Mô tả -->
                            <div class="form-group">
                                <label for="description">
                                    Mô tả sản phẩm
                                </label>

                                <textarea
                                    class="form-control"
                                    id="description"
                                    name="description"
                                    rows="5"
                                    placeholder="Nhập mô tả sản phẩm..."><?= e($product['description']) ?></textarea>
                            </div>

                            <!-- Hình ảnh -->
                            <div class="form-group">
                                <label for="image">
                                    Hình ảnh sản phẩm
                                </label>

                                <input
                                    class="form-control"
                                    type="file"
                                    id="image"
                                    name="image"
                                    accept=".jpg,.jpeg,.png,.webp,.gif">

                                <small class="form-text text-muted">
                                    Để trống nếu muốn giữ ảnh hiện tại.
                                    Ảnh mới tối đa 5 MB; định dạng JPG,
                                    JPEG, PNG, WEBP hoặc GIF.
                                </small>

                                <div class="current-image">
                                    <label>Ảnh hiện tại:</label>
                                    <br>

                                    <?php
                                    $currentImage = basename(
                                        (string)($product['image'] ?? '')
                                    );

                                    $currentImagePath = dirname(__DIR__, 2)
                                        . DIRECTORY_SEPARATOR . 'images'
                                        . DIRECTORY_SEPARATOR . $currentImage;
                                    ?>

                                    <?php if (
                                        $currentImage !== '' &&
                                        is_file($currentImagePath)
                                    ): ?>

                                        <img
                                            src="../../assets/images/<?= rawurlencode($currentImage) ?>"
                                            alt="Ảnh sản phẩm hiện tại">

                                    <?php else: ?>

                                        <p>Chưa có ảnh hoặc ảnh không tồn tại.</p>

                                    <?php endif; ?>
                                </div>

                                <!-- Xem trước ảnh mới -->
                                <div id="preview-container"
                                     class="current-image"
                                     style="display:none;">

                                    <label>Ảnh mới được chọn:</label>
                                    <br>

                                    <img id="image-preview"
                                         src=""
                                         alt="Xem trước ảnh mới">

                                </div>
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

                                    <option
                                        value="0"
                                        <?= (string)$product['is_featured'] === '0'
                                            ? 'selected' : '' ?>>

                                        Không

                                    </option>

                                    <option
                                        value="1"
                                        <?= (string)$product['is_featured'] === '1'
                                            ? 'selected' : '' ?>>

                                        Có

                                    </option>

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
                                    class="btn btn-primary">

                                    <i class="fas fa-save"></i>
                                    Lưu thay đổi

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

        // Xem trước ảnh mới trước khi lưu
        document.getElementById('image').addEventListener('change', function () {
            const file = this.files[0];
            const previewContainer =
                document.getElementById('preview-container');
            const preview = document.getElementById('image-preview');

            if (!file) {
                preview.src = '';
                previewContainer.style.display = 'none';
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert('Vui lòng chọn một tệp hình ảnh hợp lệ!');
                this.value = '';
                preview.src = '';
                previewContainer.style.display = 'none';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert('Ảnh không được vượt quá 5 MB!');
                this.value = '';
                preview.src = '';
                previewContainer.style.display = 'none';
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                preview.src = event.target.result;
                previewContainer.style.display = 'block';
            };

            reader.readAsDataURL(file);
        });
    </script>

</body>
</html>

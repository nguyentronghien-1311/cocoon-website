```php
<?php
// File: backend/categories/cats_update.php

require_once __DIR__ . '/../connect.php';

if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối MySQLi trong backend/connect.php.');
}

// Lấy ID danh mục từ URL
$categoryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$categoryId || $categoryId <= 0) {
    header('Location: admin_cats.php');
    exit;
}

// Lấy thông tin danh mục
$stmt = $connect->prepare(
    'SELECT id, name FROM categories WHERE id = ?'
);

if (!$stmt) {
    die('Không thể chuẩn bị truy vấn: ' .
        htmlspecialchars($connect->error, ENT_QUOTES, 'UTF-8'));
}

$stmt->bind_param('i', $categoryId);
$stmt->execute();

$result = $stmt->get_result();
$category = $result->fetch_assoc();
$stmt->close();

if (!$category) {
    header('Location: admin_cats.php?not_found=1');
    exit;
}

function e($value): string
{
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sửa thông tin danh mục</title>

    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.2/css/all.min.css">

    <style>
        .category-form {
            max-width: 800px;
        }

        .category-form .form-group {
            margin-bottom: 20px;
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
            color: white;
        }

        .btn-cancel:hover {
            background-color: #5a6268;
            color: white;
        }
    </style>
</head>

<body class="app sidebar-mini rtl">

<header class="app-header">
    <a class="app-sidebar__toggle"
       href="#"
       data-toggle="sidebar"
       aria-label="Ẩn thanh bên"></a>

    <ul class="app-nav">
        <li>
            <a class="app-nav__item" href="../../index.php">
                <i class="bx bx-log-out bx-rotate-180"></i>
            </a>
        </li>
    </ul>
</header>

<div class="app-sidebar__overlay" data-toggle="sidebar"></div>

<aside class="app-sidebar">
    <div class="app-sidebar__user">
        <img class="app-sidebar__user-avatar"
             src="../../images/cover-2.png"
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
            <a class="app-menu__item" href="../dashboard.php">
                <i class="app-menu__icon bx bx-tachometer"></i>
                <span class="app-menu__label">Tổng quan Admin</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item active" href="admin_cats.php">
                <i class="app-menu__icon bx bx-folder"></i>
                <span class="app-menu__label">Quản lý danh mục</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item" href="../products/admin_pros.php">
                <i class="app-menu__icon bx bx-box"></i>
                <span class="app-menu__label">Quản lý sản phẩm</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item" href="../orders/admin_orders.php">
                <i class="app-menu__icon bx bx-shopping-bag"></i>
                <span class="app-menu__label">Quản lý đơn hàng</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item" href="../../users.php">
                <i class="app-menu__icon bx bx-user"></i>
                <span class="app-menu__label">Quản lý người dùng</span>
            </a>
        </li>
    </ul>
</aside>

<main class="app-content">

    <div class="app-title">
        <ul class="app-breadcrumb breadcrumb">
            <li class="breadcrumb-item">
                <a href="admin_cats.php">Danh sách danh mục</a>
            </li>
            <li class="breadcrumb-item active">
                Sửa thông tin danh mục
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tile category-form">

                <h3 class="tile-title">Sửa thông tin danh mục</h3>

                <div class="tile-body">
                    <form method="post" action="cats_update_process.php">

                        <input type="hidden"
                               name="cat_id"
                               value="<?= e($category['id']) ?>">

                        <div class="form-group">
                            <label for="cat_id">ID danh mục</label>
                            <input
                                class="form-control"
                                type="text"
                                id="cat_id"
                                value="<?= e($category['id']) ?>"
                                readonly>
                        </div>

                        <div class="form-group">
                            <label for="cat_name">Tên danh mục</label>
                            <input
                                class="form-control"
                                type="text"
                                id="cat_name"
                                name="cat_name"
                                value="<?= e($category['name']) ?>"
                                maxlength="255"
                                required>
                        </div>

                        <div class="form-actions">
                            <button
                                type="submit"
                                name="sua"
                                value="1"
                                class="btn btn-save">
                                <i class="fas fa-save"></i>
                                Lưu lại
                            </button>

                            <a class="btn btn-cancel"
                               href="admin_cats.php">
                                <i class="fas fa-times"></i>
                                Hủy bỏ
                            </a>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

</main>

<script src="../js/jquery-3.2.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/main.js"></script>
<script src="../js/plugins/pace.min.js"></script>

</body>
</html>
```
<?php
require_once __DIR__ . '/../admin_guard.php'; // chỉ admin được vào
// File: backend/categories/cats_products.php
// Hiển thị sản phẩm theo danh mục trong cocoon_db

require_once __DIR__ . '/../connect.php';

if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối MySQLi trong backend/connect.php.');
}

// Kiểm tra ID danh mục
$categoryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$categoryId || $categoryId <= 0) {
    die('ID danh mục không hợp lệ.');
}

// Lấy thông tin danh mục
$stmtCategory = $connect->prepare(
    'SELECT id, name FROM categories WHERE id = ?'
);
$stmtCategory->bind_param('i', $categoryId);
$stmtCategory->execute();

$category = $stmtCategory->get_result()->fetch_assoc();
$stmtCategory->close();

if (!$category) {
    die('Không tìm thấy danh mục.');
}

// Lấy sản phẩm thuộc danh mục đang chọn
$sql = "SELECT p.id, p.name, p.price, p.image, p.description,
               p.ingredients, p.usage_guide, p.stock, p.category_id,
               c.name AS category_name, p.volume, p.is_featured, p.created_at
        FROM products AS p
        LEFT JOIN categories AS c ON p.category_id = c.id
        WHERE p.category_id = ?
        ORDER BY p.id DESC";

$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $categoryId);
$stmt->execute();
$result = $stmt->get_result();

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
    <title>Sản phẩm - <?= e($category['name']) ?></title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" type="text/css" href="../../assets/css/admin.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet"
          href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css">
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet"
          href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">

    <style>
        .category-heading {
            margin: 0;
            font-size: 24px;
        }

        .category-info {
            margin: 12px 0 20px;
            color: #666;
        }

        #sampleTable td {
            vertical-align: middle;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border: 1px solid #eee;
            border-radius: 4px;
            padding: 3px;
        }

        .back-button {
            display: inline-block;
            margin-bottom: 15px;
        }
    </style>
</head>

<body onload="time()" class="app sidebar-mini rtl">

<header class="app-header">
    <a class="app-sidebar__toggle"
       href="#"
       data-toggle="sidebar"
       aria-label="Ẩn/hiện thanh bên"></a>

    <ul class="app-nav">
        <li>
            <a class="app-nav__item" href="../../index.php"
               title="Về trang chủ">
                <i class="bx bx-log-out bx-rotate-180"></i>
            </a>
        </li>
    </ul>
</header>

<div class="app-sidebar__overlay" data-toggle="sidebar"></div>

<aside class="app-sidebar">
    <div class="app-sidebar__user">
        <img class="app-sidebar__user-avatar"
             src="../images/cover-2.png"
             width="50"
             alt="Admin">

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
            <a class="app-menu__item active" href="admin_cats.php">
                <i class="app-menu__icon bx bx-purchase-tag-alt"></i>
                <span class="app-menu__label">Quản lý danh mục</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item"
               href="../products/admin_pros.php">
                <i class="app-menu__icon bx bx-id-card"></i>
                <span class="app-menu__label">Quản lý sản phẩm</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item"
               href="../orders/admin_ords.php">
                <i class="app-menu__icon bx bx-user-voice"></i>
                <span class="app-menu__label">Quản lý đơn hàng</span>
            </a>
        </li>

        
    </ul>
</aside>

<main class="app-content">

    <div class="app-title">
        <ul class="app-breadcrumb breadcrumb side">
            <li class="breadcrumb-item">
                <a href="admin_cats.php">Quản lý danh mục</a>
            </li>
            <li class="breadcrumb-item active">
                <b><?= e($category['name']) ?></b>
            </li>
        </ul>

        <div id="clock"></div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="tile-body">

                    <a href="admin_cats.php"
                       class="btn btn-secondary btn-sm back-button">
                        <i class="fas fa-arrow-left"></i>
                        Quay lại danh mục
                    </a>

                    <h3 class="category-heading">
                        Sản phẩm thuộc danh mục:
                        <?= e($category['name']) ?>
                    </h3>

                    <p class="category-info">
                        Tổng số sản phẩm:
                        <strong><?= $result->num_rows ?></strong>
                    </p>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered"
                               id="sampleTable">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Mô tả</th>
                                    <th>Thành phần</th>
                                    <th>Hướng dẫn sử dụng</th>
                                    <th>Giá tiền</th>
                                    <th>Số lượng</th>
                                    <th>Kích cỡ</th>
                                    <th>Nổi bật</th>
                                    <th>Mã danh mục</th>
                                    <th>Hình ảnh</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$row['id'] ?></td>

                                    <td><?= e($row['name']) ?></td>

                                    <td><?= e($row['description']) ?></td>

                                    <td><?= e($row['ingredients']) ?></td>

                                    <td><?= e($row['usage_guide']) ?></td>

                                    <td>
                                        <?= number_format(
                                            (float)$row['price'],
                                            0,
                                            ',',
                                            '.'
                                        ) ?> VNĐ
                                    </td>

                                    <td><?= e($row['stock']) ?></td>

                                    <td><?= e($row['volume']) ?></td>

                                    <td><?= (int)$row['is_featured'] === 1 ? 'Có' : 'Không' ?></td>

                                    <td><?= (int)$row['category_id'] ?></td>

                                    <td>
                                        <?php if (!empty($row['image'])): ?>
                                            <img
                                                class="product-image"
                                                src="../../assets/images/<?= rawurlencode(basename($row['image'])) ?>"
                                                alt="<?= e($row['name']) ?>"
                                                loading="lazy"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">

                                            <span style="display:none;color:#dc3545;">
                                                Không tải được ảnh
                                            </span>
                                        <?php else: ?>
                                            <span>Chưa có ảnh</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= e($row['created_at']) ?></td>

                                    
                                </tr>
                            <?php endwhile; ?>
                            </tbody>

                        </table>
                    </div>

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
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>

<script>
    if (window.jQuery && jQuery.fn.DataTable) {
        jQuery('#sampleTable').DataTable({
            pageLength: 10,
            order: []
        });
    }

    function time() {
        const today = new Date();

        const weekday = [
            'Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư',
            'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'
        ];

        const pad = n => String(n).padStart(2, '0');

        const dateText =
            weekday[today.getDay()] + ', ' +
            pad(today.getDate()) + '/' +
            pad(today.getMonth() + 1) + '/' +
            today.getFullYear();

        const timeText =
            pad(today.getHours()) + ' giờ ' +
            pad(today.getMinutes()) + ' phút ' +
            pad(today.getSeconds()) + ' giây';

        const clock = document.getElementById('clock');

        if (clock) {
            clock.textContent = dateText + ' - ' + timeText;
        }

        setTimeout(time, 1000);
    }
</script>

</body>
</html>

<?php
$stmt->close();
?>

<?php
// File: backend/categories/admin_cats.php
// Danh sách danh mục cho cơ sở dữ liệu cocoon_db

require_once __DIR__ . '/../connect.php';

if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối MySQLi trong backend/connect.php. Hãy kiểm tra tên biến kết nối ($connect).');
}

// Xử lý xóa danh mục khi người dùng xác nhận.
// Nếu danh mục đang được sản phẩm sử dụng, database có thể từ chối xóa để bảo toàn dữ liệu.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    $categoryId = filter_var($_POST['delete_category_id'], FILTER_VALIDATE_INT);
    if ($categoryId !== false && $categoryId !== null && $categoryId > 0) {
        $stmt = $connect->prepare('DELETE FROM categories WHERE id = ?');
        if (!$stmt) {
            die('Không thể chuẩn bị truy vấn xóa: ' . htmlspecialchars($connect->error, ENT_QUOTES, 'UTF-8'));
        }
        $stmt->bind_param('i', $categoryId);
        if (!$stmt->execute()) {
            $deleteError = $stmt->error;
        }
        $stmt->close();
    }
    // Tránh gửi lại biểu mẫu khi tải lại trang.
    header('Location: admin_cats.php' . (isset($deleteError) ? '?delete_error=1' : '?deleted=1'));
    exit;
}

$sql = "SELECT c.id, c.name, COUNT(p.id) AS product_count
        FROM categories AS c
        LEFT JOIN products AS p ON p.category_id = c.id
        GROUP BY c.id, c.name
        ORDER BY c.id DESC";
$result = $connect->query($sql);
if (!$result) {
    die('Lỗi truy vấn danh mục: ' . htmlspecialchars($connect->error, ENT_QUOTES, 'UTF-8'));
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Quản trị Admin</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Giữ nguyên bộ giao diện quản trị hiện tại -->
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
</head>
<body onload="time()" class="app sidebar-mini rtl">
<header class="app-header">
    <a class="app-sidebar__toggle" href="#" data-toggle="sidebar" aria-label="Hide Sidebar"></a>
    <ul class="app-nav">
        <li><a class="app-nav__item" href="../../index.php"><i class='bx bx-log-out bx-rotate-180'></i></a></li>
    </ul>
</header>
<div class="app-sidebar__overlay" data-toggle="sidebar"></div>
<aside class="app-sidebar">
    <div class="app-sidebar__user">
        <img class="app-sidebar__user-avatar" src="../../images/cover-2.png" width="50px" alt="User Image">
        <div>
            <p class="app-sidebar__user-name"><b>Admin</b></p>
            <p class="app-sidebar__user-designation">Chào mừng bạn trở lại</p>
        </div>
    </div>
    <hr>
    <ul class="app-menu">
        <li><a class="app-menu__item haha" href="../dashboard.php"><i class='app-menu__icon bx bx-tachometer'></i><span class="app-menu__label">Tổng quan Admin</span></a></li>
        <li><a class="app-menu__item active" href="admin_cats.php"><i class='app-menu__icon bx bx-tachometer'></i><span class="app-menu__label">Quản lý danh mục</span></a></li>
        <li><a class="app-menu__item" href="../products/admin_pros.php"><i class='app-menu__icon bx bx-id-card'></i><span class="app-menu__label">Quản lý sản phẩm</span></a></li>
        <li><a class="app-menu__item" href="../orders/admin_orders.php"><i class='app-menu__icon bx bx-user-voice'></i><span class="app-menu__label">Quản lý đơn hàng</span></a></li>
        <li><a class="app-menu__item" href="../../users.php"><i class='app-menu__icon bx bx-purchase-tag-alt'></i><span class="app-menu__label">Quản lý người dùng</span></a></li>
    </ul>
</aside>
<main class="app-content">
    <div class="app-title">
        <ul class="app-breadcrumb breadcrumb side">
            <li class="breadcrumb-item active"><a href="#"><b>Danh sách danh mục</b></a></li>
        </ul>
        <div id="clock"></div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="tile-body">
                    <div class="row element-button">
                        <div class="col-sm-2">
                            <a class="btn btn-add btn-sm" href="cats_create.php" title="Thêm"><i class="fas fa-plus"></i> Thêm danh mục mới</a>
                        </div>
                    </div>
                    <?php if (isset($_GET['delete_error'])): ?>
                        <p role="alert">Không thể xóa danh mục. Có thể danh mục này đang được sản phẩm sử dụng.</p>
                    <?php elseif (isset($_GET['deleted'])): ?>
                        <p>Đã xử lý yêu cầu xóa danh mục.</p>
                    <?php endif; ?>
                    <table class="table table-hover table-bordered" id="sampleTable">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên danh mục</th>
                            <th>Số sản phẩm</th>
                            <th>Hành động</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($row['id']) ?></td>
                                <td><?= e($row['name']) ?></td>
                                <td><?= e($row['product_count']) ?></td>
                                <td>
                                    <a href="cats_update.php?id=<?= (int)$row['id'] ?>" class="btn btn-primary btn-sm edit"><i class="fas fa-edit"></i> Sửa</a>
                                    <form method="post" action="admin_cats.php" style="display:inline" onsubmit="return confirm('Bạn có thật sự muốn xóa danh mục này không?');">
                                        <input type="hidden" name="delete_category_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm trash"><i class="fas fa-trash-alt"></i> Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
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
        jQuery('#sampleTable').DataTable();
    }
    function time() {
        const today = new Date();
        const weekday = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
        const pad = n => String(n).padStart(2, '0');
        const dateText = weekday[today.getDay()] + ', ' + pad(today.getDate()) + '/' + pad(today.getMonth() + 1) + '/' + today.getFullYear();
        const timeText = pad(today.getHours()) + ' giờ ' + pad(today.getMinutes()) + ' phút ' + pad(today.getSeconds()) + ' giây';
        const clock = document.getElementById('clock');
        if (clock) clock.innerHTML = '<span class="date">' + dateText + ' - ' + timeText + '</span>';
        setTimeout(time, 1000);
    }
</script>
</body>
</html>

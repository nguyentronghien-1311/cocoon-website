<?php
// File: backend/orders/admin_ords.php
// Danh sách đơn hàng cho cơ sở dữ liệu cocoon_db

require_once __DIR__ . '/../connect.php';

if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối MySQLi trong backend/connect.php. Hãy kiểm tra tên biến kết nối ($connect).');
}

$sql = "SELECT id, customer_name, customer_phone, customer_address,
               total_amount, status, payment_method, user_id, note, created_at
        FROM orders
        ORDER BY id DESC";
$result = $connect->query($sql);
if (!$result) {
    die('Lỗi truy vấn đơn hàng: ' . htmlspecialchars($connect->error, ENT_QUOTES, 'UTF-8'));
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
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">
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
        <li><a class="app-menu__item" href="../categories/admin_cats.php"><i class='app-menu__icon bx bx-tachometer'></i><span class="app-menu__label">Quản lý danh mục</span></a></li>
        <li><a class="app-menu__item" href="../products/admin_pros.php"><i class='app-menu__icon bx bx-id-card'></i><span class="app-menu__label">Quản lý sản phẩm</span></a></li>
        <li><a class="app-menu__item active" href="admin_ords.php"><i class='app-menu__icon bx bx-user-voice'></i><span class="app-menu__label">Quản lý đơn hàng</span></a></li>
        <li><a class="app-menu__item" href="../../users.php"><i class='app-menu__icon bx bx-purchase-tag-alt'></i><span class="app-menu__label">Quản lý người dùng</span></a></li>
    </ul>
</aside>
<main class="app-content">
    <div class="app-title">
        <ul class="app-breadcrumb breadcrumb side">
            <li class="breadcrumb-item active"><a href="#"><b>Danh sách đơn hàng</b></a></li>
        </ul>
        <div id="clock"></div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="tile-body">
                    <table class="table table-hover table-bordered" id="sampleTable">
                        <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Tên khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Địa chỉ</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thanh toán</th>
                            <th>Ngày đặt</th>
                            <th>Ghi chú</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($row['id']) ?></td>
                                <td><?= e($row['customer_name']) ?></td>
                                <td><?= e($row['customer_phone']) ?></td>
                                <td><?= e($row['customer_address']) ?></td>
                                <td><?= number_format((float)$row['total_amount'], 0, ',', '.') ?> ₫</td>
                                <td><?= e($row['status']) ?></td>
                                <td><?= e($row['payment_method']) ?></td>
                                <td><?= e($row['created_at']) ?></td>
                                <td><?= e($row['note'] ?: '—') ?></td>
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

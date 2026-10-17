<?php
require_once __DIR__ . '/admin_guard.php'; // chỉ admin được vào
// File: backend/dashboard.php
// Dashboard quản trị website Cocoon

require_once __DIR__ . '/connect.php';

if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối MySQLi.');
}

mysqli_set_charset($connect, 'utf8mb4');

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($value): string {
    return number_format((float)$value, 0, ',', '.') . ' ₫';
}

// Thống kê tổng số sản phẩm
$productResult = $connect->query("SELECT COUNT(*) AS total FROM products");
$totalProducts = $productResult
    ? (int)$productResult->fetch_assoc()['total']
    : 0;

// Thống kê tổng số danh mục
$categoryResult = $connect->query("SELECT COUNT(*) AS total FROM categories");
$totalCategories = $categoryResult
    ? (int)$categoryResult->fetch_assoc()['total']
    : 0;

// Thống kê đơn hàng và doanh thu
$orderStatsSql = "
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(
            SUM(CASE WHEN status = 'Chờ duyệt' THEN 1 ELSE 0 END),
            0
        ) AS pending_orders,
        COALESCE(
            SUM(CASE WHEN status = 'Đã giao' THEN total_amount ELSE 0 END),
            0
        ) AS revenue
    FROM orders
";

$orderStatsResult = $connect->query($orderStatsSql);
$orderStats = $orderStatsResult
    ? $orderStatsResult->fetch_assoc()
    : [];

$totalOrders = (int)($orderStats['total_orders'] ?? 0);
$pendingOrders = (int)($orderStats['pending_orders'] ?? 0);
$revenue = (float)($orderStats['revenue'] ?? 0);

// Lấy 8 đơn hàng mới nhất
$recentOrdersSql = "
    SELECT id, customer_name, total_amount, status, created_at
    FROM orders
    ORDER BY created_at DESC, id DESC
    LIMIT 8
";

$recentOrders = $connect->query($recentOrdersSql);

// Màu trạng thái đơn hàng
function statusClass($status): string {
    switch ($status) {
        case 'Chờ duyệt':
            return 'pending';
        case 'Đã duyệt':
            return 'approved';
        case 'Đang giao':
            return 'shipping';
        case 'Đã giao':
            return 'delivered';
        default:
            return 'other';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard - Cocoon Admin</title>

    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        .dashboard-welcome {
            margin-bottom: 22px;
        }

        .dashboard-welcome h2 {
            margin: 0 0 7px;
            color: #292929;
            font-size: 25px;
        }

        .dashboard-welcome p {
            margin: 0;
            color: #888;
            font-size: 14px;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
            padding: 22px 18px;
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .025);
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            border-radius: 12px;
            font-size: 26px;
        }

        .stat-icon.products {
            background: #e8f1ff;
            color: #3974d8;
        }

        .stat-icon.categories {
            background: #f1eaff;
            color: #8056c7;
        }

        .stat-icon.orders {
            background: #fff1df;
            color: #d88927;
        }

        .stat-icon.revenue {
            background: #e4f6eb;
            color: #198754;
        }

        .stat-info {
            min-width: 0;
        }

        .stat-info p {
            margin: 0 0 8px;
            color: #777;
            font-size: 13px;
        }

        .stat-info h3 {
            margin: 0;
            color: #292929;
            font-size: 23px;
            overflow-wrap: anywhere;
        }

        .stat-info .revenue-number {
            font-size: 19px;
        }

        .dashboard-section {
            margin-bottom: 24px;
            padding: 22px;
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .025);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }

        .section-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
        }

        .view-all {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #198754;
            font-size: 13px;
            text-decoration: none;
            white-space: nowrap;
        }

        .view-all:hover {
            color: #146c43;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 17px;
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            text-decoration: none;
            transition: .2s;
        }

        .quick-action:hover {
            border-color: #198754;
            background: #f7fcf8;
            transform: translateY(-2px);
        }

        .quick-action > i {
            font-size: 26px;
            color: #198754;
        }

        .quick-action strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-size: 14px;
        }

        .quick-action span {
            color: #888;
            font-size: 12px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .recent-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .recent-table th,
        .recent-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .recent-table th {
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            white-space: nowrap;
        }

        .recent-table tbody tr:hover {
            background: #fafafa;
        }

        .recent-table td {
            color: #444;
        }

        .order-code {
            color: #198754;
            font-weight: 600;
            text-decoration: none;
        }

        .order-code:hover {
            text-decoration: underline;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.pending {
            color: #946200;
            background: #fff1cf;
        }

        .status-badge.approved {
            color: #2457a7;
            background: #e7efff;
        }

        .status-badge.shipping {
            color: #9a4c0b;
            background: #ffead8;
        }

        .status-badge.delivered {
            color: #176b3a;
            background: #e2f5e9;
        }

        .status-badge.other {
            color: #555;
            background: #eee;
        }

        .empty-state {
            padding: 30px 10px;
            color: #888;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .dashboard-section {
                padding: 15px;
            }

            .stat-card {
                padding: 17px;
            }

            .dashboard-welcome h2 {
                font-size: 21px;
            }

            .section-header h3 {
                font-size: 16px;
            }
        }
    </style>
</head>

<body class="app sidebar-mini">

<header class="app-header">
    <a class="app-sidebar__toggle" href="#"
       data-toggle="sidebar" aria-label="Ẩn thanh điều hướng"></a>

    <ul class="app-nav">
        <li>
            <a class="app-nav__item" href="../index.php" title="Về trang chủ">
                <i class="bx bx-log-out bx-rotate-180"></i>
            </a>
        </li>
    </ul>
</header>

<div class="app-sidebar__overlay" data-toggle="sidebar"></div>

<aside class="app-sidebar">
    <div class="app-sidebar__user">
        <img class="app-sidebar__user-avatar"
             src="images/cover-2.png"
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
            <a class="app-menu__item haha active" href="dashboard.php">
                <i class="app-menu__icon bx bx-tachometer"></i>
                <span class="app-menu__label">Tổng quan Admin</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item" href="categories/admin_cats.php">
                <i class="app-menu__icon bx bx-purchase-tag-alt"></i>
                <span class="app-menu__label">Quản lý danh mục</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item" href="products/admin_pros.php">
                <i class="app-menu__icon bx bx-id-card"></i>
                <span class="app-menu__label">Quản lý sản phẩm</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item" href="orders/admin_ords.php">
                <i class="app-menu__icon bx bx-receipt"></i>
                <span class="app-menu__label">Quản lý đơn hàng</span>
            </a>
        </li>
    </ul>
</aside>

<main class="app-content">

    <div class="app-title">
        <ul class="app-breadcrumb breadcrumb side">
            <li class="breadcrumb-item active">
                <b>Tổng quan Admin</b>
            </li>
        </ul>
        <div id="clock"></div>
    </div>

    <div class="dashboard-welcome">
        <h2>Xin chào, Admin!</h2>
        <p>Tổng quan tình hình kinh doanh trên website Cocoon.</p>
    </div>

    <!-- Các thẻ thống kê -->
    <div class="dashboard-stats">

        <div class="stat-card">
            <div class="stat-icon products">
                <i class="bx bx-package"></i>
            </div>
            <div class="stat-info">
                <p>Tổng sản phẩm</p>
                <h3><?= number_format($totalProducts) ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon categories">
                <i class="bx bx-category"></i>
            </div>
            <div class="stat-info">
                <p>Tổng danh mục</p>
                <h3><?= number_format($totalCategories) ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orders">
                <i class="bx bx-receipt"></i>
            </div>
            <div class="stat-info">
                <p>Tổng đơn hàng</p>
                <h3><?= number_format($totalOrders) ?></h3>
                <small style="color:#999;">
                    <?= number_format($pendingOrders) ?> đơn chờ duyệt
                </small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon revenue">
                <i class="bx bx-wallet"></i>
            </div>
            <div class="stat-info">
                <p>Doanh thu đơn đã giao</p>
                <h3 class="revenue-number"><?= money($revenue) ?></h3>
            </div>
        </div>

    </div>

    <!-- Truy cập nhanh -->
    <section class="dashboard-section">
        <div class="section-header">
            <h3><i class="bx bx-grid-alt"></i> Truy cập nhanh</h3>
        </div>

        <div class="quick-actions">

            <a href="products/admin_pros.php" class="quick-action">
                <i class="bx bx-package"></i>
                <div>
                    <strong>Quản lý sản phẩm</strong>
                    <span>Thêm, sửa và quản lý sản phẩm</span>
                </div>
            </a>

            <a href="categories/admin_cats.php" class="quick-action">
                <i class="bx bx-category"></i>
                <div>
                    <strong>Quản lý danh mục</strong>
                    <span>Sắp xếp các danh mục sản phẩm</span>
                </div>
            </a>

            <a href="orders/admin_ords.php" class="quick-action">
                <i class="bx bx-receipt"></i>
                <div>
                    <strong>Quản lý đơn hàng</strong>
                    <span>Kiểm tra và cập nhật đơn hàng</span>
                </div>
            </a>

        </div>
    </section>

    <!-- Đơn hàng gần đây -->
    <section class="dashboard-section">

        <div class="section-header">
            <h3><i class="bx bx-time-five"></i> Đơn hàng gần đây</h3>

            <a href="orders/admin_ords.php" class="view-all">
                Xem tất cả <i class="bx bx-right-arrow-alt"></i>
            </a>
        </div>

        <div class="table-responsive">
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Chi tiết</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>

                    <?php while ($row = $recentOrders->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a class="order-code"
                                   href="orders/order_detail.php?id=<?= (int)$row['id'] ?>">
                                    #<?= (int)$row['id'] ?>
                                </a>
                            </td>

                            <td><?= e($row['customer_name']) ?></td>

                            <td><?= e($row['created_at']) ?></td>

                            <td><?= money($row['total_amount']) ?></td>

                            <td>
                                <span class="status-badge <?= e(statusClass($row['status'])) ?>">
                                    <?= e($row['status']) ?>
                                </span>
                            </td>

                            <td>
                                <a class="view-all"
                                   href="orders/order_detail.php?id=<?= (int)$row['id'] ?>">
                                    Xem <i class="bx bx-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            Chưa có đơn hàng nào.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<script src="js/jquery-3.2.1.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/main.js"></script>
<script src="js/plugins/pace.min.js"></script>

<script>
    function updateClock() {
        const now = new Date();
        const weekdays = [
            'Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư',
            'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'
        ];

        const pad = n => String(n).padStart(2, '0');

        const date =
            weekdays[now.getDay()] + ', ' +
            pad(now.getDate()) + '/' +
            pad(now.getMonth() + 1) + '/' +
            now.getFullYear();

        const time =
            pad(now.getHours()) + ' giờ ' +
            pad(now.getMinutes()) + ' phút ' +
            pad(now.getSeconds()) + ' giây';

        const clock = document.getElementById('clock');

        if (clock) {
            clock.textContent = date + ' - ' + time;
        }
    }

    updateClock();
    setInterval(updateClock, 1000);
</script>

</body>
</html>

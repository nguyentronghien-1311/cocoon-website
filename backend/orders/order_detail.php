<?php
require_once __DIR__ . '/../admin_guard.php'; // chỉ admin được vào
// File: backend/orders/order_detail.php
// Chi tiết đơn hàng - Cocoon Admin

require_once __DIR__ . '/../connect.php';

if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối MySQLi.');
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Kiểm tra mã đơn hàng
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    die('Mã đơn hàng không hợp lệ.');
}

// Lấy thông tin đơn hàng
$sql = "SELECT id, customer_name, customer_phone, customer_address,
               total_amount, status, payment_method, user_id, note, created_at
        FROM orders
        WHERE id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    http_response_code(404);
    die('Không tìm thấy đơn hàng.');
}

// Khởi tạo session để tạo mã bảo vệ biểu mẫu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['order_csrf_token'])) {
    $_SESSION['order_csrf_token'] = bin2hex(random_bytes(32));
}

// Thứ tự trạng thái hợp lệ
$orderStatuses = [
    'Chờ duyệt',
    'Đã duyệt',
    'Đang giao',
    'Đã giao'
];

$statusMessage = '';
$statusMessageType = '';

// Xử lý khi nhấn nút Lưu trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['save_status'])) {

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($csrfToken)
        || !hash_equals($_SESSION['order_csrf_token'], $csrfToken)) {

        $statusMessage = 'Yêu cầu không hợp lệ. Vui lòng tải lại trang.';
        $statusMessageType = 'error';

    } else {
        $newStatus = $_POST['status'] ?? '';
        $currentStatus = $order['status'];

        $currentIndex = array_search(
            $currentStatus,
            $orderStatuses,
            true
        );

        $newIndex = array_search(
            $newStatus,
            $orderStatuses,
            true
        );

        if ($currentIndex === false) {
            $statusMessage = 'Trạng thái hiện tại chưa được cấu hình. '
                           . 'Vui lòng kiểm tra dữ liệu trong database.';
            $statusMessageType = 'error';

        } elseif ($newIndex === false) {
            $statusMessage = 'Trạng thái được chọn không hợp lệ.';
            $statusMessageType = 'error';

        } elseif ($newIndex !== $currentIndex + 1) {
            $statusMessage = 'Chỉ được chuyển sang trạng thái kế tiếp, '
                           . 'không được giữ nguyên hoặc chuyển ngược.';
            $statusMessageType = 'error';

        } else {
            // Chỉ cập nhật nếu trạng thái trong DB vẫn là trạng thái cũ
            $updateSql = "UPDATE orders
                          SET status = ?
                          WHERE id = ? AND status = ?";

            $updateStmt = $connect->prepare($updateSql);
            $updateStmt->bind_param(
                'sis',
                $newStatus,
                $orderId,
                $currentStatus
            );

            $updateStmt->execute();

            if ($updateStmt->affected_rows === 1) {
                $order['status'] = $newStatus;
                $statusMessage = 'Cập nhật trạng thái đơn hàng thành công.';
                $statusMessageType = 'success';
            } else {
                $statusMessage = 'Trạng thái đơn hàng đã thay đổi hoặc '
                               . 'không thể cập nhật. Vui lòng tải lại trang.';
                $statusMessageType = 'error';
            }

            $updateStmt->close();
        }
    }
}

// Lấy danh sách sản phẩm trong đơn hàng
$sqlItems = "SELECT p.name AS product_name,
                    oi.product_id,
                    oi.quantity,
                    oi.price,
                    (oi.quantity * oi.price) AS subtotal
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?";

$stmtItems = $connect->prepare($sqlItems);
$stmtItems->bind_param('i', $orderId);
$stmtItems->execute();
$items = $stmtItems->get_result();

function money($value): string {
    return number_format((float)$value, 0, ',', '.') . ' ₫';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chi tiết đơn hàng #<?= (int)$order['id'] ?></title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">

    <style>
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .detail-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 22px;
            margin-bottom: 20px;
        }

        .detail-card h3 {
            font-size: 17px;
            margin: 0 0 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
            color: #333;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #f2f2f2;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #777;
            min-width: 125px;
        }

        .info-value {
            text-align: right;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .order-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            background: #f0f0f0;
        }

        .order-total {
            color: #c62828;
            font-size: 21px;
            font-weight: 700;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th,
        .product-table td {
            padding: 13px 12px;
            border: 1px solid #e5e5e5;
            vertical-align: middle;
        }

        .product-table th {
            background: #f7f7f7;
            white-space: nowrap;
        }

        .text-right {
            text-align: right;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #6c757d;
            color: #fff;
            padding: 9px 14px;
            border-radius: 4px;
            text-decoration: none;
            margin-bottom: 18px;
        }

        .back-button:hover {
            color: #fff;
            background: #565e64;
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .detail-card {
                padding: 15px;
            }

            .info-row {
                flex-wrap: wrap;
            }
        }

        
        

/* Khung cập nhật trạng thái */
.status-panel {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 22px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.025);
}

.status-panel-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 22px;
}

.status-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #e7f5ec;
    color: #198754;
    font-size: 23px;
    flex-shrink: 0;
}

.status-panel-title {
    margin: 0 0 5px !important;
    padding: 0 !important;
    border: none !important;
    font-size: 18px !important;
    color: #333;
}

.status-panel-subtitle {
    margin: 0;
    font-size: 13px;
    color: #888;
}

.status-current-label {
    margin-bottom: 9px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.8px;
    color: #888;
}

.status-transition {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 13px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.status-badge.current {
    background: #fff0d6;
    color: #946200;
}

.status-badge.next {
    background: #e7f5ec;
    color: #198754;
}

.status-arrow {
    color: #aaa;
    font-size: 20px;
}


/* Khu vực cập nhật trạng thái */
.status-form-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.status-field {
    width: 520px;
    max-width: 65%;
}

.status-field label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
}

.status-field select {
    width: 100%;
    height: 44px;
    padding: 0 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fff;
    box-sizing: border-box;
}

.status-field small {
    display: block;
    margin-top: 8px;
    color: #888;
    font-size: 12px;
}

.save-status-btn {
    height: 44px;
    margin-top: 26px;
    padding: 0 20px;
    border: none;
    border-radius: 6px;
    background: #198754;
    color: white;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
}

.save-status-btn:hover:not(:disabled) {
    background: #146c43;
}

.save-status-btn:disabled {
    background: #b8c5bd;
    cursor: not-allowed;
}

@media (max-width: 600px) {
    .status-form-row {
        flex-direction: column;
    }

    .status-field {
        width: 100%;
        max-width: 100%;
    }

    .save-status-btn {
        width: 100%;
        margin-top: 0;
    }
}


    </style>
</head>

<body class="app sidebar-mini rtl">

<header class="app-header">
    <a class="app-sidebar__toggle" href="#"
       data-toggle="sidebar" aria-label="Ẩn thanh điều hướng"></a>

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
            <a class="app-menu__item" href="../categories/admin_cats.php">
                <i class="app-menu__icon bx bx-purchase-tag-alt"></i>
                <span class="app-menu__label">Quản lý danh mục</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item" href="../products/admin_pros.php">
                <i class="app-menu__icon bx bx-id-card"></i>
                <span class="app-menu__label">Quản lý sản phẩm</span>
            </a>
        </li>

        <li>
            <a class="app-menu__item active" href="admin_ords.php">
                <i class="app-menu__icon bx bx-receipt"></i>
                <span class="app-menu__label">Quản lý đơn hàng</span>
            </a>
        </li>
    </ul>
</aside>

<main class="app-content">

    <div class="app-title">
        <ul class="app-breadcrumb breadcrumb side">
            <li class="breadcrumb-item">
                <a href="admin_ords.php">Quản lý đơn hàng</a>
            </li>
            <li class="breadcrumb-item active">
                <b>Chi tiết đơn #<?= (int)$order['id'] ?></b>
            </li>
        </ul>
        <div id="clock"></div>
    </div>

    
<a href="admin_ords.php" class="back-button">
    <i class="bx bx-arrow-back"></i>
    Quay lại danh sách
</a>

<div class="detail-card">
    <h3>
        <i class="bx bx-refresh"></i>
        Cập nhật trạng thái đơn hàng
    </h3>

    <?php if ($statusMessage !== ''): ?>
        <div class="status-message <?= e($statusMessageType) ?>">
            <?= e($statusMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          action="order_detail.php?id=<?= (int)$order['id'] ?>">

        <input type="hidden"
               name="csrf_token"
               value="<?= e($_SESSION['order_csrf_token']) ?>">

        <div class="status-form-row">
            <div class="status-field">
                <label for="status">Trạng thái hiện tại / trạng thái mới</label>

                <select name="status" id="status" required>
                    <?php
                    $currentIndex = array_search(
                        $order['status'],
                        $orderStatuses,
                        true
                    );
                    ?>

                    <?php foreach ($orderStatuses as $index => $status): ?>
                        <?php
                        // Chỉ cho chọn đúng trạng thái kế tiếp.
                        // Trạng thái hiện tại vẫn hiển thị nhưng bị khóa.
                        $disabled = (
                            $currentIndex === false
                            || $index !== $currentIndex + 1
                        );
                        ?>

                        <option
                            value="<?= e($status) ?>"
                            <?= $disabled ? 'disabled' : '' ?>
                            <?= $status === $order['status'] ? 'selected' : '' ?>
                        >
                            <?= e($status) ?>
                            <?= $status === $order['status']
                                ? ' (Hiện tại)'
                                : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <small>
                    Chỉ có thể chuyển sang trạng thái kế tiếp.
                </small>
            </div>

            <button type="submit"
                    name="save_status"
                    value="1"
                    class="save-status-btn"
                    <?= $currentIndex === false
                        || $currentIndex >= count($orderStatuses) - 1
                        ? 'disabled'
                        : '' ?>
                    onclick="return confirm('Bạn có chắc chắn muốn cập nhật trạng thái đơn hàng?');">
                Lưu
            </button>
        </div>
    </form>
</div>


    <div class="tile">
        <div class="tile-title">
            <h3>
                <i class="bx bx-receipt"></i>
                Chi tiết đơn hàng #<?= (int)$order['id'] ?>
            </h3>
        </div>

        <div class="tile-body">

            <div class="detail-grid">

                <!-- Thông tin người nhận -->
                <div class="detail-card">
                    <h3>
                        Thông tin người nhận
                    </h3>

                    <div class="info-row">
                        <span class="info-label">Họ và tên</span>
                        <span class="info-value">
                            <?= e($order['customer_name']) ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Số điện thoại</span>
                        <span class="info-value">
                            <?= e($order['customer_phone']) ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Địa chỉ giao hàng</span>
                        <span class="info-value">
                            <?= e($order['customer_address']) ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Mã khách hàng</span>
                        <span class="info-value">
                            <?= e($order['user_id'] ?: 'Khách vãng lai') ?>
                        </span>
                    </div>
                </div>

                <!-- Thông tin đơn hàng -->
                <div class="detail-card">
                    <h3>
                        Thông tin đơn hàng
                    </h3>

                    <div class="info-row">
                        <span class="info-label">Mã đơn hàng</span>
                        <span class="info-value">
                            #<?= (int)$order['id'] ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Ngày đặt</span>
                        <span class="info-value">
                            <?= e($order['created_at']) ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Trạng thái</span>
                        <span class="info-value">
                            <span class="order-status">
                                <?= e($order['status']) ?>
                            </span>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Thanh toán</span>
                        <span class="info-value">
                            <?= e($order['payment_method']) ?>
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Ghi chú</span>
                        <span class="info-value">
                            <?= e($order['note'] ?: 'Không có') ?>
                        </span>
                    </div>
                </div>

            </div>

            <!-- Danh sách sản phẩm -->
            <div class="detail-card">
                <h3>
                    Danh sách sản phẩm
                </h3>

                <div class="table-responsive">
                    <table class="product-table">
                        <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã sản phẩm</th>
                            <th>Tên sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if ($items->num_rows > 0): ?>
                            <?php $stt = 1; ?>
                            <?php while ($item = $items->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $stt++ ?></td>

                                    <td>
                                        <?= e($item['product_id']) ?>
                                    </td>

                                    <td>
                                        <?= e($item['product_name'] ?: 'Sản phẩm không còn tồn tại') ?>
                                    </td>

                                    <td class="text-right">
                                        <?= money($item['price']) ?>
                                    </td>

                                    <td class="text-right">
                                        <?= (int)$item['quantity'] ?>
                                    </td>

                                    <td class="text-right">
                                        <?= money($item['subtotal']) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">
                                    Đơn hàng chưa có dữ liệu sản phẩm
                                    hoặc tên bảng chi tiết đơn hàng chưa chính xác.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="text-align:right; margin-top:20px;">
                    <span style="color:#666;">Tổng tiền đơn hàng</span>
                    <div class="order-total">
                        <?= money($order['total_amount']) ?>
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

<script>
    function time() {
        const today = new Date();
        const weekdays = [
            'Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư',
            'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'
        ];

        const pad = n => String(n).padStart(2, '0');

        const dateText =
            weekdays[today.getDay()] + ', ' +
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

    time();
</script>

</body>
</html>
<?php
$stmtItems->close();
?>

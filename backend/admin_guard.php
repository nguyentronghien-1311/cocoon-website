<?php
/**
 * admin_guard.php - Chặn người không phải admin vào trang quản trị.
 * Cách dùng: dòng đầu mỗi trang admin
 *     require_once __DIR__ . '/../admin_guard.php';   (trang trong backend/xxx/)
 *     require_once __DIR__ . '/admin_guard.php';      (trang trong backend/)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('cocoon_admin_login_url')) {
    function cocoon_admin_login_url(): string
    {
        // Tìm thư mục gốc của web (phần đứng trước /backend/ trong đường dẫn)
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $pos = strpos($script, '/backend/');
        $base = $pos !== false ? substr($script, 0, $pos) : rtrim(dirname($script), '/');
        return $base . '/login.php';
    }
}

$cocoonRole = $_SESSION['user_role'] ?? null;

// Phiên đăng nhập cũ (trước khi có user_role): đọc quyền từ database
if ($cocoonRole === null && !empty($_SESSION['user_id'])) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $cocoonDb = @mysqli_connect('localhost', 'root', '', 'cocoon_db');
    if ($cocoonDb) {
        $stmt = $cocoonDb->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $uid = (int) $_SESSION['user_id'];
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $cocoonRole = $row['role'] ?? null;
        $_SESSION['user_role'] = $cocoonRole;
        $cocoonDb->close();
    }
}

if ($cocoonRole !== 'admin') {
    header('Location: ' . cocoon_admin_login_url() . '?admin_required=1');
    exit;
}

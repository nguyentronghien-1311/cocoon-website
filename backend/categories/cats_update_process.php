<?php
require_once __DIR__ . '/../admin_guard.php'; // chỉ admin được vào
// File: backend/categories/cats_update_process.php
// Xử lý cập nhật tên danh mục trong CSDL cocoon_db

require_once __DIR__ . '/../connect.php';

// Kiểm tra kết nối database
if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối cơ sở dữ liệu.');
}

// Chỉ xử lý khi nhận dữ liệu từ form POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['sua'])) {
    header('Location: admin_cats.php');
    exit;
}

// Lấy dữ liệu từ form
$categoryId = filter_input(INPUT_POST, 'cat_id', FILTER_VALIDATE_INT);
$categoryName = trim($_POST['cat_name'] ?? '');

// Kiểm tra ID hợp lệ
if ($categoryId === false || $categoryId === null || $categoryId <= 0) {
    header('Location: admin_cats.php?update_error=invalid_id');
    exit;
}

// Kiểm tra tên danh mục
if ($categoryName === '') {
    header('Location: cats_update.php?id=' . $categoryId . '&error=empty_name');
    exit;
}

// Kiểm tra tên không vượt quá 255 ký tự
if (mb_strlen($categoryName, 'UTF-8') > 255) {
    header('Location: cats_update.php?id=' . $categoryId . '&error=name_too_long');
    exit;
}

// Cập nhật tên danh mục, giữ nguyên ID
$stmt = $connect->prepare(
    'UPDATE categories SET name = ? WHERE id = ?'
);

if (!$stmt) {
    die('Không thể chuẩn bị truy vấn cập nhật: ' .
        htmlspecialchars($connect->error, ENT_QUOTES, 'UTF-8'));
}

$stmt->bind_param('si', $categoryName, $categoryId);

if (!$stmt->execute()) {
    $stmt->close();
    header('Location: cats_update.php?id=' . $categoryId . '&error=update_failed');
    exit;
}

$stmt->close();

// Cập nhật thành công
header('Location: admin_cats.php?updated=1');
exit;
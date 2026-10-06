<?php
// 1. Khai báo thông tin cấu hình máy chủ XAMPP
$servername = "localhost";
$username = "root";       // Mặc định của XAMPP luôn là root
$password = "";           // Mặc định của XAMPP luôn để trống
$dbname = "cocoon_db";    // Tên database nhóm đã thống nhất tạo trong XAMPP

// 2. Tạo kết nối đến MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// 3. Kiểm tra kết nối, nếu lỗi thì báo ngay để sửa
if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

// 4. Thiết lập font chữ tiếng Việt chuẩn UTF-8 để không bị lỗi chữ Cocoon
$conn->set_charset("utf8mb4");
?>

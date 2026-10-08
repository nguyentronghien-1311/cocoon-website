<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: index.php');
	exit;
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
	http_response_code(403);
	exit('Yêu cầu đăng xuất không hợp lệ. Vui lòng tải lại trang và thử lại.');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
	$cookie = session_get_cookie_params();
	setcookie(session_name(), '', [
		'expires' => time() - 42000,
		'path' => $cookie['path'],
		'domain' => $cookie['domain'],
		'secure' => $cookie['secure'],
		'httponly' => $cookie['httponly'],
		'samesite' => $cookie['samesite'] ?? 'Lax',
	]);
}

session_destroy();
header('Location: index.php');
exit;
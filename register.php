<?php
session_start();

if (isset($_SESSION['user_id'])) {
	header('Location: index.php');
	exit;
}

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$fullName = '';
$username = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = $_POST['csrf_token'] ?? '';
	$fullName = trim((string) ($_POST['full_name'] ?? ''));
	$username = trim((string) ($_POST['username'] ?? ''));
	$email = trim((string) ($_POST['email'] ?? ''));
	$phone = trim((string) ($_POST['phone'] ?? ''));
	$address = trim((string) ($_POST['address'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');
	$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

	if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
		$error = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
	} elseif ($fullName === '' || strlen($fullName) > 100) {
		$error = 'Vui lòng nhập họ tên hợp lệ (tối đa 100 ký tự).';
	} elseif (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
		$error = 'Tên đăng nhập cần có 3-30 ký tự: chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
		$error = 'Vui lòng nhập địa chỉ email hợp lệ.';
	} elseif ($phone === '' || strlen($phone) > 20) {
		$error = 'Vui lòng nhập số điện thoại hợp lệ (tối đa 20 ký tự).';
	} elseif ($address === '' || strlen($address) > 255) {
		$error = 'Vui lòng nhập địa chỉ hợp lệ (tối đa 255 ký tự).';
	} elseif (strlen($password) < 8) {
		$error = 'Mật khẩu cần có ít nhất 8 ký tự.';
	} elseif ($password !== $passwordConfirm) {
		$error = 'Mật khẩu xác nhận không khớp.';
	} else {
		try {
			$pdo = new PDO(
				'mysql:host=localhost;dbname=cocoon_db;charset=utf8mb4',
				'root',
				'',
				[
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES => false,
				]
			);

			$check = $pdo->prepare('SELECT username, email FROM users WHERE username = :username OR email = :email LIMIT 1');
			$check->execute([':username' => $username, ':email' => $email]);
			$existingUser = $check->fetch();

			if ($existingUser) {
				$error = strcasecmp($existingUser['username'], $username) === 0
					? 'Tên đăng nhập đã được sử dụng.'
					: 'Địa chỉ email đã được sử dụng.';
			} else {
				$pdo->beginTransaction();
				$insert = $pdo->prepare(
					'INSERT INTO users (username, full_name, email, phone, address, password) VALUES (:username, :full_name, :email, :phone, :address, :password)'
				);
				$insert->execute([
					':username' => $username,
					':full_name' => $fullName,
					':email' => $email,
					':phone' => $phone,
					':address' => $address,
					':password' => password_hash($password, PASSWORD_DEFAULT),
				]);
				$userId = (int) $pdo->lastInsertId();
				$addressInsert = $pdo->prepare(
					'INSERT INTO user_addresses (user_id, label, recipient_name, phone, address, is_default) VALUES (:user_id, :label, :recipient_name, :phone, :address, 1)'
				);
				$addressInsert->execute([
					':user_id' => $userId,
					':label' => 'Nhà riêng',
					':recipient_name' => $fullName,
					':phone' => $phone,
					':address' => $address,
				]);
				$pdo->commit();

				session_regenerate_id(true);
				$_SESSION['user_id'] = $userId;
				$_SESSION['user_name'] = $fullName;
				$_SESSION['user_email'] = $email;
				unset($_SESSION['csrf_token']);
				header('Location: index.php');
				exit;
			}
		} catch (PDOException $e) {
			if (isset($pdo) && $pdo->inTransaction()) {
				$pdo->rollBack();
			}
			error_log($e->getMessage());
			$error = 'Không thể tạo tài khoản lúc này. Vui lòng thử lại sau.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="theme-color" content="#ffffff">
	<title>Đăng ký - Cocoon</title>
	<style>
		:root {
			color-scheme: light;
			--green: #2E7D32;
			--green-dark: #256629;
			--brown: #8D6E63;
			--ink: #26332a;
			--muted: #718076;
			--line: #dfe7df;
			--danger: #c62828;
			--danger-bg: #fff1f1;
		}

		* { box-sizing: border-box; }
		body {
			min-height: 100vh;
			margin: 0;
			display: grid;
			place-items: center;
			padding: 32px 20px;
			background: #fff;
			color: var(--ink);
			font-family: "Segoe UI", Arial, Helvetica, sans-serif;
		}
		.register-shell { width: min(100%, 480px); }
		.brand {
			display: block;
			width: fit-content;
			margin: 0 auto 24px;
			color: var(--green);
			font-size: 25px;
			font-weight: 750;
			letter-spacing: 3px;
			text-decoration: none;
		}
		.register-card {
			padding: 38px 42px 34px;
			border: 1px solid #edf1ed;
			border-radius: 12px;
			background: #fff;
			box-shadow: 0 18px 55px rgba(38, 51, 42, 0.09), 0 2px 8px rgba(38, 51, 42, 0.035);
		}
		.eyebrow {
			margin: 0 0 10px;
			color: var(--brown);
			font-size: 12px;
			font-weight: 700;
			letter-spacing: 1.4px;
			text-align: center;
			text-transform: uppercase;
		}
		h1 { margin: 0; color: var(--green); font-size: 28px; font-weight: 650; text-align: center; }
		.intro { margin: 10px 0 26px; color: var(--muted); font-size: 14px; line-height: 1.6; text-align: center; }
		.field { margin-bottom: 16px; }
		label { display: block; margin-bottom: 7px; color: #35443a; font-size: 14px; font-weight: 600; }
		input,
		textarea {
			width: 100%;
			min-height: 46px;
			padding: 11px 13px;
			border: 1px solid var(--line);
			border-radius: 6px;
			background: #fff;
			color: var(--ink);
			font: inherit;
			font-size: 15px;
		}
		textarea { min-height: 84px; resize: vertical; }
		input:focus,
		textarea:focus { border-color: var(--green); outline: none; box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.12); }
		input::placeholder { color: #a0aaa2; }
		.alert {
			margin: 0 0 16px;
			padding: 12px 14px;
			border: 1px solid rgba(198, 40, 40, 0.2);
			border-radius: 8px;
			background: var(--danger-bg);
			color: var(--danger);
			font-size: 14px;
			line-height: 1.5;
		}
		.submit-button {
			width: 100%;
			min-height: 50px;
			border: 0;
			border-radius: 6px;
			background: var(--green);
			color: #fff;
			font: inherit;
			font-size: 15px;
			font-weight: 700;
			cursor: pointer;
		}
		.submit-button:hover { background: var(--green-dark); }
		.login-prompt { margin: 23px 0 0; color: var(--muted); font-size: 14px; text-align: center; }
		.login-prompt a { color: var(--brown); font-weight: 650; text-decoration: none; }
		.login-prompt a:hover { color: var(--green); text-decoration: underline; text-underline-offset: 3px; }
		@media (max-width: 480px) {
			body { padding: 22px 16px; }
			.register-card { padding: 30px 24px 27px; }
			h1 { font-size: 25px; }
		}
	</style>
</head>
<body>
	<main class="register-shell">
		<a class="brand" href="index.php" aria-label="Cocoon - về trang chủ">COCOON</a>
		<section class="register-card" aria-labelledby="register-title">
			<p class="eyebrow">Mỹ phẩm thuần chay Việt Nam</p>
			<h1 id="register-title">Tạo tài khoản</h1>
			<p class="intro">Tham gia Cocoon để lưu lại hành trình chăm sóc của bạn.</p>

			<?php if ($error !== ''): ?>
				<div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
			<?php endif; ?>

			<form action="" method="post">
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
				<div class="field">
					<label for="full_name">Họ và tên</label>
					<input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" autocomplete="name" maxlength="100" required>
				</div>
				<div class="field">
					<label for="username">Tên đăng nhập</label>
					<input type="text" id="username" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" minlength="3" maxlength="30" pattern="[A-Za-z0-9_.-]{3,30}" required>
				</div>
				<div class="field">
					<label for="email">Email</label>
					<input type="email" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" maxlength="150" required>
				</div>
				<div class="field">
					<label for="phone">Số điện thoại</label>
					<input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>" autocomplete="tel" maxlength="20" required>
				</div>
				<div class="field">
					<label for="address">Địa chỉ</label>
					<textarea id="address" name="address" autocomplete="street-address" maxlength="255" rows="3" required><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
				</div>
				<div class="field">
					<label for="password">Mật khẩu</label>
					<input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
				</div>
				<div class="field">
					<label for="password_confirm">Xác nhận mật khẩu</label>
					<input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
				</div>
				<button class="submit-button" type="submit">Tạo tài khoản</button>
			</form>

			<p class="login-prompt">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
		</section>
	</main>
</body>
</html>
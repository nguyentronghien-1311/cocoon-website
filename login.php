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
$savedUsername = $_COOKIE['cocoon_remember_user'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $error = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } elseif ($username === '' || $password === '') {
        $error = 'Vui lòng nhập tên đăng nhập/email và mật khẩu.';
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

            $stmt = $pdo->prepare(
                'SELECT id, username, email, password, full_name FROM users WHERE username = :username OR email = :email LIMIT 1'
            );
            $stmt->execute([
                ':username' => $username,
                ':email' => $username,
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];
                $_SESSION['user_email'] = $user['email'];

                if ($remember) {
                    setcookie('cocoon_remember_user', $user['username'], time() + 30 * 24 * 60 * 60, '/', '', false, true);
                } else {
                    setcookie('cocoon_remember_user', '', time() - 3600, '/', '', false, true);
                }

                header('Location: index.php');
                exit;
            }

            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.';
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
	<title>Đăng nhập - Cocoon</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
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

		* {
			box-sizing: border-box;
		}

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

		.login-shell {
			width: min(100%, 440px);
		}

		.brand {
			display: flex;
			flex-direction: column;
			align-items: center;
			width: fit-content;
			margin: 0 auto 24px;
			color: #3E3228;
			font-family: "Cormorant Garamond", Georgia, serif;
			font-style: italic;
			font-weight: 500;
			text-decoration: none;
		}

		.brand-the {
			margin-bottom: -5px;
			font-size: 16px;
			line-height: 1;
		}

		.brand-name {
			font-size: 34px;
			line-height: 0.9;
		}

		.brand-country {
			margin-top: 5px;
			color: var(--brown);
			font-family: "Segoe UI", Arial, Helvetica, sans-serif;
			font-size: 10px;
			font-style: normal;
			letter-spacing: 0.38em;
		}

		.login-card {
			padding: 40px 42px 36px;
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

		h1 {
			margin: 0;
			color: var(--green);
			font-size: 28px;
			font-weight: 650;
			text-align: center;
		}

		.intro {
			margin: 10px 0 30px;
			color: var(--muted);
			font-size: 14px;
			line-height: 1.6;
			text-align: center;
		}

		.field {
			margin-bottom: 19px;
		}

		label {
			display: block;
			margin-bottom: 8px;
			color: #35443a;
			font-size: 14px;
			font-weight: 600;
		}

		input[type="text"],
		input[type="password"] {
			width: 100%;
			min-height: 48px;
			padding: 12px 14px;
			border: 1px solid var(--line);
			border-radius: 6px;
			background: #fff;
			color: var(--ink);
			font: inherit;
			font-size: 15px;
			transition: border-color 160ms ease, box-shadow 160ms ease;
		}

		input::placeholder {
			color: #a0aaa2;
		}

		input[type="text"]:focus,
		input[type="password"]:focus {
			border-color: var(--green);
			outline: none;
			box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.12);
		}

		.remember-row {
			display: flex;
			align-items: center;
			gap: 9px;
			margin: 3px 0 24px;
			color: #536157;
			font-size: 14px;
			cursor: pointer;
		}

		.remember-row input {
			width: 16px;
			height: 16px;
			margin: 0;
			accent-color: var(--green);
		}

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
			transition: background-color 160ms ease, transform 160ms ease;
		}

		.submit-button:hover {
			background: var(--green-dark);
		}

		.submit-button:active {
			transform: translateY(1px);
		}

		.register-prompt {
			margin: 25px 0 0;
			color: var(--muted);
			font-size: 14px;
			text-align: center;
		}

		.register-prompt a {
			color: var(--brown);
			font-weight: 650;
			text-decoration: none;
		}

		.register-prompt a:hover {
			color: var(--green);
			text-decoration: underline;
			text-underline-offset: 3px;
		}

		@media (max-width: 480px) {
			body {
				padding: 22px 16px;
			}

			.brand {
				margin-bottom: 19px;
			}

			.login-card {
				padding: 32px 24px 28px;
			}

			h1 {
				font-size: 25px;
			}
		}
	</style>
</head>
<body>
	<main class="login-shell">
		<a class="brand" href="index.php" aria-label="the cocoon Vietnam - về trang chủ">
			<span class="brand-the">the</span>
			<span class="brand-name">cocoon</span>
			<span class="brand-country">VIETNAM</span>
		</a>
		<section class="login-card" aria-labelledby="login-title">
			<p class="eyebrow">Mỹ phẩm thuần chay Việt Nam</p>
			<h1 id="login-title">Chào mừng trở lại</h1>
			<p class="intro">Đăng nhập để tiếp tục hành trình chăm sóc làn da cùng Cocoon.</p>

			<?php if ($error !== ''): ?>
				<div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
			<?php endif; ?>

			<form action="" method="post" novalidate>
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

				<div class="field">
					<label for="username">Tên đăng nhập hoặc Email</label>
					<input type="text" id="username" name="username" value="<?= htmlspecialchars($savedUsername, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nhập tên đăng nhập hoặc email" autocomplete="username" required>
				</div>

				<div class="field">
					<label for="password">Mật khẩu</label>
					<input type="password" id="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
				</div>

				<label class="remember-row" for="remember">
					<input type="checkbox" id="remember" name="remember" <?= !empty($savedUsername) ? 'checked' : '' ?>>
					<span>Ghi nhớ mật khẩu</span>
				</label>

				<button class="submit-button" type="submit">Đăng nhập</button>
			</form>

			<p class="register-prompt">Chưa có tài khoản? <a href="register.php">Đăng ký tài khoản mới</a></p>
		</section>
	</main>
</body>
</html>

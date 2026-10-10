<?php
session_start();

if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit;
}

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = (int) $_SESSION['user_id'];
$error = '';
$addresses = [];
$updated = isset($_GET['updated']);
$pdo = null;

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

	$profileQuery = $pdo->prepare('SELECT full_name, phone, address FROM users WHERE id = :user_id');
	$profileQuery->execute([':user_id' => $userId]);
	$profile = $profileQuery->fetch();

	if ($profile && $profile['address'] && $profile['phone']) {
		$countQuery = $pdo->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id');
		$countQuery->execute([':user_id' => $userId]);
		if ((int) $countQuery->fetchColumn() === 0) {
			$seedAddress = $pdo->prepare(
				'INSERT INTO user_addresses (user_id, label, recipient_name, phone, address, is_default) VALUES (:user_id, :label, :recipient_name, :phone, :address, 1)'
			);
			$seedAddress->execute([
				':user_id' => $userId,
				':label' => 'Nhà riêng',
				':recipient_name' => $profile['full_name'],
				':phone' => $profile['phone'],
				':address' => $profile['address'],
			]);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$token = (string) ($_POST['csrf_token'] ?? '');
		$action = (string) ($_POST['action'] ?? '');
		if (!hash_equals($_SESSION['csrf_token'], $token)) {
			$error = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
		} elseif ($action === 'add') {
			$label = trim((string) ($_POST['label'] ?? ''));
			$recipientName = trim((string) ($_POST['recipient_name'] ?? ''));
			$phone = trim((string) ($_POST['phone'] ?? ''));
			$address = trim((string) ($_POST['address'] ?? ''));
			$makeDefault = !empty($_POST['is_default']);

			if ($label === '' || strlen($label) > 80) {
				$error = 'Vui lòng nhập tên gợi nhớ (tối đa 80 ký tự).';
			} elseif ($recipientName === '' || strlen($recipientName) > 100) {
				$error = 'Vui lòng nhập tên người nhận hợp lệ.';
			} elseif ($phone === '' || strlen($phone) > 20) {
				$error = 'Vui lòng nhập số điện thoại hợp lệ.';
			} elseif ($address === '' || strlen($address) > 255) {
				$error = 'Vui lòng nhập địa chỉ hợp lệ (tối đa 255 ký tự).';
			} else {
				$pdo->beginTransaction();
				$countQuery = $pdo->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id');
				$countQuery->execute([':user_id' => $userId]);
				$makeDefault = $makeDefault || (int) $countQuery->fetchColumn() === 0;
				if ($makeDefault) {
					$clearDefault = $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id');
					$clearDefault->execute([':user_id' => $userId]);
				}
				$insert = $pdo->prepare(
					'INSERT INTO user_addresses (user_id, label, recipient_name, phone, address, is_default) VALUES (:user_id, :label, :recipient_name, :phone, :address, :is_default)'
				);
				$insert->execute([
					':user_id' => $userId,
					':label' => $label,
					':recipient_name' => $recipientName,
					':phone' => $phone,
					':address' => $address,
					':is_default' => $makeDefault ? 1 : 0,
				]);
				$pdo->commit();
				header('Location: addresses.php?updated=1');
				exit;
			}
		} elseif ($action === 'default') {
			$addressId = (int) ($_POST['address_id'] ?? 0);
			$ownsAddress = $pdo->prepare('SELECT id FROM user_addresses WHERE id = :id AND user_id = :user_id');
			$ownsAddress->execute([':id' => $addressId, ':user_id' => $userId]);
			if (!$ownsAddress->fetch()) {
				$error = 'Không tìm thấy địa chỉ cần cập nhật.';
			} else {
				$pdo->beginTransaction();
				$pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id')->execute([':user_id' => $userId]);
				$pdo->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = :id AND user_id = :user_id')->execute([':id' => $addressId, ':user_id' => $userId]);
				$pdo->commit();
				header('Location: addresses.php?updated=1');
				exit;
			}
		} elseif ($action === 'delete') {
			$addressId = (int) ($_POST['address_id'] ?? 0);
			$findAddress = $pdo->prepare('SELECT is_default FROM user_addresses WHERE id = :id AND user_id = :user_id');
			$findAddress->execute([':id' => $addressId, ':user_id' => $userId]);
			$addressToDelete = $findAddress->fetch();
			if (!$addressToDelete) {
				$error = 'Không tìm thấy địa chỉ cần xóa.';
			} else {
				$pdo->beginTransaction();
				$pdo->prepare('DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id')->execute([':id' => $addressId, ':user_id' => $userId]);
				if ((int) $addressToDelete['is_default'] === 1) {
					$nextDefault = $pdo->prepare('SELECT id FROM user_addresses WHERE user_id = :user_id ORDER BY id LIMIT 1');
					$nextDefault->execute([':user_id' => $userId]);
					$nextId = $nextDefault->fetchColumn();
					if ($nextId) {
						$pdo->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = :id AND user_id = :user_id')->execute([':id' => $nextId, ':user_id' => $userId]);
					}
				}
				$pdo->commit();
				header('Location: addresses.php?updated=1');
				exit;
			}
		} else {
			$error = 'Yêu cầu không hợp lệ.';
		}
	}

	$list = $pdo->prepare('SELECT id, label, recipient_name, phone, address, is_default FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, id DESC');
	$list->execute([':user_id' => $userId]);
	$addresses = $list->fetchAll();
} catch (PDOException $e) {
	if ($pdo && $pdo->inTransaction()) {
		$pdo->rollBack();
	}
	error_log($e->getMessage());
	$error = 'Không thể tải hoặc cập nhật địa chỉ. Vui lòng thử lại sau.';
}

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Địa chỉ giao hàng - Cocoon</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
	<style>
		:root { --green: #2E7D32; --green-dark: #256629; --brown: #8D6E63; --ink: #26332a; --muted: #718076; --line: #dfe7df; --bg: #f7f8f5; --danger: #c62828; }
		* { box-sizing: border-box; }
		body { min-height: 100vh; margin: 0; background: var(--bg); color: var(--ink); font-family: "Segoe UI", Arial, sans-serif; }
		a { color: inherit; text-decoration: none; }
		.topbar { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 20px; padding: 14px max(20px, calc((100% - 1080px) / 2)); background: #fff; border-bottom: 1px solid var(--line); }
		.brand { grid-column: 2; grid-row: 1; display: flex; flex-direction: column; align-items: center; color: #3E3228; font-family: "Cormorant Garamond", Georgia, serif; font-style: italic; font-weight: 500; }
		.brand-the { margin-bottom: -4px; font-size: 13px; line-height: 1; }
		.brand-name { font-size: 28px; line-height: 0.9; }
		.brand-country { margin-top: 4px; color: var(--brown); font-family: "Segoe UI", Arial, sans-serif; font-size: 8px; font-style: normal; letter-spacing: 0.38em; }
		.topbar-links { grid-column: 3; grid-row: 1; justify-self: end; display: flex; align-items: center; gap: 18px; font-size: 14px; }
		.topbar-links a:hover { color: var(--green); }
		.topbar-links form { margin: 0; }
		.topbar-links button { border: 0; padding: 0; background: transparent; color: var(--brown); font: inherit; cursor: pointer; }
		main { width: min(100% - 32px, 1080px); margin: 38px auto 64px; }
		h1 { margin: 0 0 8px; color: var(--green); font-size: 28px; }
		.intro { margin: 0 0 28px; color: var(--muted); }
		.layout { display: grid; grid-template-columns: minmax(280px, .8fr) minmax(0, 1.2fr); gap: 28px; align-items: start; }
		section { padding: 24px; border: 1px solid var(--line); background: #fff; }
		h2 { margin: 0 0 20px; font-size: 18px; }
		.field { margin-bottom: 16px; }
		label { display: block; margin-bottom: 7px; font-size: 14px; font-weight: 650; }
		input, textarea { width: 100%; padding: 11px 12px; border: 1px solid #cfd8cf; border-radius: 4px; color: var(--ink); font: inherit; }
		textarea { min-height: 86px; resize: vertical; }
		input:focus, textarea:focus { outline: 2px solid rgba(46,125,50,.18); border-color: var(--green); }
		.checkline { display: flex; align-items: center; gap: 9px; margin: 18px 0; font-weight: 400; }
		.checkline input { width: 16px; height: 16px; margin: 0; accent-color: var(--green); }
		.primary, .small-button { min-height: 40px; padding: 9px 14px; border: 0; border-radius: 4px; background: var(--green); color: #fff; font: inherit; font-weight: 650; cursor: pointer; }
		.primary:hover, .small-button:hover { background: var(--green-dark); }
		.address-list { display: grid; gap: 0; }
		.address-row { padding: 18px 0; border-top: 1px solid var(--line); }
		.address-row:first-child { padding-top: 0; border-top: 0; }
		.address-head { display: flex; justify-content: space-between; gap: 14px; align-items: start; }
		.address-head h3 { margin: 0; font-size: 16px; }
		.default-badge { padding: 3px 7px; background: #edf5ed; color: var(--green-dark); font-size: 12px; white-space: nowrap; }
		.address-row p { margin: 7px 0 0; color: #536157; line-height: 1.5; }
		.address-actions { display: flex; gap: 16px; margin-top: 12px; }
		.address-actions button { border: 0; padding: 0; background: transparent; color: var(--green-dark); font: inherit; font-size: 13px; cursor: pointer; }
		.address-actions .delete { color: var(--danger); }
		.notice { margin-bottom: 20px; padding: 12px 14px; background: #edf5ed; color: var(--green-dark); }
		.alert { margin-bottom: 20px; padding: 12px 14px; background: #fff1f1; color: var(--danger); }
		.empty { color: var(--muted); }
		@media (max-width: 720px) {
			.topbar { grid-template-columns: 1fr; justify-items: center; padding: 14px 16px; }
			.brand, .topbar-links { grid-column: 1; }
			.topbar-links { grid-row: 2; justify-self: center; margin-left: 0; }
			.topbar-links { flex-wrap: wrap; gap: 12px; }
			.layout { grid-template-columns: 1fr; }
		}
	</style>
</head>
<body>
	<header class="topbar">
		<a class="brand" href="index.php" aria-label="the cocoon Vietnam - Trang chủ">
			<span class="brand-the">the</span>
			<span class="brand-name">cocoon</span>
			<span class="brand-country">VIETNAM</span>
		</a>
		<nav class="topbar-links" aria-label="Điều hướng tài khoản">
			<a href="products.php">Sản phẩm</a>
			<a href="cart.php">Giỏ hàng</a>
			<a href="addresses.php" aria-current="page">Địa chỉ giao hàng</a>
			<form action="logout.php" method="post">
				<input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
				<button type="submit">Đăng xuất</button>
			</form>
		</nav>
	</header>
	<main>
		<h1>Địa chỉ giao hàng</h1>
		<p class="intro">Lưu nhiều địa chỉ để chọn nhanh khi thanh toán.</p>
		<?php if ($updated): ?><div class="notice" role="status">Đã cập nhật danh sách địa chỉ.</div><?php endif; ?>
		<?php if ($error !== ''): ?><div class="alert" role="alert"><?= $escape($error) ?></div><?php endif; ?>
		<div class="layout">
			<section aria-labelledby="add-address-title">
				<h2 id="add-address-title">Thêm địa chỉ mới</h2>
				<form method="post">
					<input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
					<input type="hidden" name="action" value="add">
					<div class="field">
						<label for="label">Tên gợi nhớ</label>
						<input id="label" name="label" maxlength="80" placeholder="Nhà riêng, Công ty..." required>
					</div>
					<div class="field">
						<label for="recipient_name">Người nhận</label>
						<input id="recipient_name" name="recipient_name" maxlength="100" autocomplete="name" required>
					</div>
					<div class="field">
						<label for="phone">Số điện thoại</label>
						<input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" required>
					</div>
					<div class="field">
						<label for="address">Địa chỉ chi tiết</label>
						<textarea id="address" name="address" maxlength="255" autocomplete="street-address" required></textarea>
					</div>
					<label class="checkline"><input type="checkbox" name="is_default" value="1"> Đặt làm địa chỉ mặc định</label>
					<button class="primary" type="submit">Lưu địa chỉ</button>
				</form>
			</section>
			<section aria-labelledby="saved-addresses-title">
				<h2 id="saved-addresses-title">Địa chỉ đã lưu</h2>
				<?php if (!$addresses): ?>
					<p class="empty">Bạn chưa lưu địa chỉ nào.</p>
				<?php else: ?>
					<div class="address-list">
						<?php foreach ($addresses as $savedAddress): ?>
							<article class="address-row">
								<div class="address-head">
									<h3><?= $escape($savedAddress['label']) ?></h3>
									<?php if ((int) $savedAddress['is_default'] === 1): ?><span class="default-badge">Mặc định</span><?php endif; ?>
								</div>
								<p><?= $escape($savedAddress['recipient_name']) ?> · <?= $escape($savedAddress['phone']) ?><br><?= $escape($savedAddress['address']) ?></p>
								<div class="address-actions">
									<?php if ((int) $savedAddress['is_default'] !== 1): ?>
										<form method="post">
											<input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
											<input type="hidden" name="action" value="default">
											<input type="hidden" name="address_id" value="<?= (int) $savedAddress['id'] ?>">
											<button type="submit">Đặt làm mặc định</button>
										</form>
									<?php endif; ?>
									<form method="post" onsubmit="return confirm('Xóa địa chỉ này?')">
										<input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="address_id" value="<?= (int) $savedAddress['id'] ?>">
										<button class="delete" type="submit">Xóa</button>
									</form>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		</div>
	</main>
</body>
</html>
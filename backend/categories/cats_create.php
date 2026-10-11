<?php
// File: backend/categories/cats_create.php
// Thêm danh mục vào cơ sở dữ liệu cocoon_db

require_once __DIR__ . '/../connect.php';

if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối MySQLi trong backend/connect.php. Hãy kiểm tra biến $connect.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them'])) {
    $name = trim($_POST['cat_name'] ?? '');

    if ($name === '') {
        $error = 'Vui lòng nhập tên danh mục.';
    } else {
        // Bảng categories trong cocoon_db hiện sử dụng các trường id và name.
        $stmt = $connect->prepare('INSERT INTO categories (name) VALUES (?)');

        if (!$stmt) {
            $error = 'Không thể chuẩn bị truy vấn: ' . $connect->error;
        } else {
            $stmt->bind_param('s', $name);

            if ($stmt->execute()) {
                $stmt->close();
                header('Location: admin_cats.php');
                exit;
            }

            $error = 'Không thể thêm danh mục: ' . $stmt->error;
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>Quản trị Admin</title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Main CSS-->
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
  <!-- or -->
  <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">

  <!-- Font-icon css-->
  <link rel="stylesheet" type="text/css"
    href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
</head>

<body class="app sidebar-mini rtl">
<style>
    .Choicefile {
      display: block;
      background: #14142B;
      border: 1px solid #fff;
      color: #fff;
      width: 150px;
      text-align: center;
      text-decoration: none;
      cursor: pointer;
      padding: 5px 0px;
      border-radius: 5px;
      font-weight: 500;
      align-items: center;
      justify-content: center;
    }

    .Choicefile:hover {
      text-decoration: none;
      color: white;
    }

    #uploadfile,
    .removeimg {
      display: none;
    }

    #thumbbox {
      position: relative;
      width: 100%;
      margin-bottom: 20px;
    }

    .removeimg {
      height: 25px;
      position: absolute;
      background-repeat: no-repeat;
      top: 5px;
      left: 5px;
      background-size: 25px;
      width: 25px;
      border-radius: 50%;
    }

    .removeimg::before {
      -webkit-box-sizing: border-box;
      box-sizing: border-box;
      content: '';
      border: 1px solid red;
      background: red;
      text-align: center;
      display: block;
      margin-top: 11px;
      transform: rotate(45deg);
    }

    .removeimg::after {
      content: '';
      background: red;
      border: 1px solid red;
      text-align: center;
      display: block;
      transform: rotate(-45deg);
      margin-top: -2px;
    }
  </style>
  <!-- Navbar-->
  <header class="app-header">
    <!-- Sidebar toggle button--><a class="app-sidebar__toggle" href="#" data-toggle="sidebar"
      aria-label="Hide Sidebar"></a>
    <!-- Navbar Right Menu-->
    <ul class="app-nav">
      <!-- User Menu-->
      <li><a class="app-nav__item" href="/index.php"><i class='bx bx-log-out bx-rotate-180'></i> </a>
      </li>
    </ul>
  </header>
  <!-- Sidebar menu-->
  <div class="app-sidebar__overlay" data-toggle="sidebar"></div>
  <aside class="app-sidebar">
    <div class="app-sidebar__user"><img class="app-sidebar__user-avatar" src="../../images/cover-2.png" width="50px"
        alt="User Image">
      <div>
        <p class="app-sidebar__user-name"><b>Admin</b></p>
        <p class="app-sidebar__user-designation">Chào mừng bạn trở lại</p>
      </div>
    </div>
    <hr>
    <ul class="app-menu">
      <li><a class="app-menu__item haha" href="../dashboard.php"><i class='app-menu__icon bx bx-tachometer'></i>
          <span class="app-menu__label">Tổng quan Admin</span></a></li>
      <li><a class="app-menu__item active" href="admin_cats.php"><i class='app-menu__icon bx bx-tachometer'></i><span
            class="app-menu__label">Quản lý danh mục</span></a></li>
      <li><a class="app-menu__item" href="../products/admin_pros.php"><i class='app-menu__icon bx bx-id-card'></i> <span
            class="app-menu__label">Quản lý sản phẩm</span></a></li>
      <li><a class="app-menu__item" href="../orders/admin_ords.php"><i class='app-menu__icon bx bx-user-voice'></i><span
            class="app-menu__label">Quản lý đơn hàng</span></a></li>
      <li><a class="app-menu__item" href="../../login.php"><i class='app-menu__icon bx bx-purchase-tag-alt'></i><span
            class="app-menu__label">Quản lý người dùng</span></a></li>
    </ul>
  </aside>
  <main class="app-content">
    <div class="app-title">
      <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item">Danh sách danh mục</li>
        <li class="breadcrumb-item"><a href="#">Thêm danh mục</a></li>
      </ul>
    </div>
  <form method="post" enctype="multipart/form-data">
    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <h3 class="tile-title">Tạo danh mục mới</h3>
          <?php if ($error !== ''): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
          <div class="row">
            <div class="form-group col-md-4">
              <label class="control-label">Tên danh mục</label>
              <input class="form-control" type="text" name="cat_name" value="<?php echo htmlspecialchars($_POST['cat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="form-group col-md-4">
              <label class="control-label">Mô tả</label>
              <textarea class="form-control" type="text" name="cat_des"></textarea>
            </div>
            <input type="hidden" name="created_at" value="<?php echo date('Y-m-d H:i:s'); ?>">
          </div>
        </div>
      </div>
      <div class="row">
      <div class="col-md-12" style="padding-left: 30px;">
        <input class="btn btn-save" type="submit" value="Lưu lại" name="them">
        <a class="btn btn-cancel" href="admin_cats.php">Hủy bỏ</a>
      </div>
    </div>
    </div>
  </form>
  </main>

  <!-- Essential javascripts for application to work-->
  <script src="../js/jquery-3.2.1.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>
  <script src="../js/main.js"></script>
  <!-- The javascript plugin to display page loading on top-->
  <script src="../js/plugins/pace.min.js"></script>
</body>
</html>

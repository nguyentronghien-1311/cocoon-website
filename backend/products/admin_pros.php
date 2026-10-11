<?php
// File: backend/products/admin_pros.php
// Danh sách sản phẩm cho cơ sở dữ liệu cocoon_db

require_once __DIR__ . '/../connect.php';

// File connect.php hiện được giả định tạo kết nối MySQLi trong biến $connect.
if (!isset($connect) || !($connect instanceof mysqli)) {
    die('Không tìm thấy kết nối MySQLi trong backend/connect.php. Hãy kiểm tra tên biến kết nối ($connect).');
}

$sql = "SELECT p.id, p.name, p.price, p.image, p.description, p.stock,
               p.category_id, c.name AS category_name, p.volume,
               p.is_featured, p.created_at
        FROM products AS p
        LEFT JOIN categories AS c ON p.category_id = c.id
        ORDER BY p.id DESC";

$result = $connect->query($sql);
if (!$result) {
    die('Lỗi truy vấn sản phẩm: ' . htmlspecialchars($connect->error, ENT_QUOTES, 'UTF-8'));
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
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



        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css" />



      <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>



        <!-- Font-icon css-->

        <link rel="stylesheet" type="text/css"  href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

        <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">

        <script src="../ckeditor/ckeditor.js"></script>

      </head>



<body onload="time()" class="app sidebar-mini rtl">

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

    <div class="app-sidebar__user"><img class="app-sidebar__user-avatar" src="../img/cover-2.png" width="50px"

        alt="User Image">

      <div>

        <p class="app-sidebar__user-name"><b>Admin</b></p>

        <p class="app-sidebar__user-designation">Chào mừng bạn trở lại</p>

      </div>

    </div>

    <hr>

    <ul class="app-menu">

      <li><a class="app-menu__item haha" href="phan-mem-ban-hang.php"><i class='app-menu__icon bx bx-tachometer'></i>

          <span class="app-menu__label">Tổng quan Admin</span></a></li>

      <li><a class="app-menu__item " href="../categories/admin_cats.php"><i class='app-menu__icon bx bx-tachometer'></i><span

            class="app-menu__label">Quản lý danh mục</span></a></li>

      <li><a class="app-menu__item active " href="admin_products.php"><i class='app-menu__icon bx bx-id-card'></i> <span

            class="app-menu__label">Quản lý sản phẩm</span></a></li>

      <li><a class="app-menu__item" href="../orders/admin_ords.php"><i class='app-menu__icon bx bx-user-voice'></i><span

            class="app-menu__label">Quản lý đơn hàng</span></a></li>

      <li><a class="app-menu__item" href="admin_users.php"><i class='app-menu__icon bx bx-purchase-tag-alt'></i><span&#x20;

            class="app-menu__label">Quản lý người dùng</span></a></li>

    </ul>

  </aside>

    <main class="app-content">

        <div class="app-title">

            <ul class="app-breadcrumb breadcrumb side">

                <li class="breadcrumb-item active"><a href="#"><b>Danh sách sản phẩm</b></a></li>

            </ul>

            <div id="clock"></div>

        </div>

        <div class="row">

            <div class="col-md-12">

                <div class="tile">

                    <div class="tile-body">

                        <div class="row element-button">

                            <div class="col-sm-2">

                            <a class="btn btn-add btn-sm" href="add-product.php" title="Thêm"><i class="fas fa-plus"></i>

                                Thêm sản phẩm mới </a>

                            </div>              

                            <div class="col-sm-2">

                              <a class="btn btn-excel btn-sm" href="" title="In"><i class="fas fa-file-excel"></i> Xuất Excel</a>

                            </div>

                            <div class="col-sm-2">

                              <a class="btn btn-delete btn-sm pdf-file" type="button" title="In" onclick="myFunction(this)"><i

                                  class="fas fa-file-pdf"></i> Xuất PDF</a>

                            </div>

                            <div class="col-sm-2">

                              <a class="btn btn-delete btn-sm" type="button" title="Xóa" onclick="myFunction(this)"><i

                                  class="fas fa-trash-alt"></i> Xóa tất cả </a>

                            </div>

                          </div>

                        <table class="table table-hover table-bordered" id="sampleTable">

                        <thead>

                                <tr>

                                    <th>ID</th>

                                    <th>Tên sản phẩm</th>

                                    <th>Thương hiệu</th>

                                    <th>Mô tả</th>

                                    <th>Giá tiền</th>

                                    <th>Số lượng</th>

                                    <th>Giới tính</th>

                                    <th>Kích cỡ</th>

                                    <th>Mã danh mục</th>

                                    <th>Hình ảnh</th>

                                    <th>Ngày tạo</th>

                                    <th>Hành động</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($row = $result->fetch_assoc()) { ?>

            <tr>

                <td><?php echo $row['id']; ?></td>

                <td><?php echo $row['name']; ?></td>

                <td><?php echo '—'; ?></td>

                <td><?php echo $row['description']; ?></td>

                <td><?php echo number_format($row['price'], 0, '.', ','); ?></td>

                <td><?php echo $row['stock']; ?></td>

                <td><?php echo '—'; ?></td>

                <td><?php echo $row['volume']; ?></td>

                <td><?php echo $row['category_id']; ?></td>

                <td><img src='../../images/<?=$row["image"]?>' height='200px'></td>

                <td><?php echo $row['created_at']; ?></td>



                <td><a href="delete-product.php?id=<?= $row['id']?>" onclick="deleteRow('<?=$row['id']?>')" class="btn btn-primary btn-sm trash"  

                                            ><i class="fas fa-trash-alt">Xóa</i>&#x20;

                    </a>

                <a href="edit-product.php?id=<?= $row['id']?>" class="btn btn-primary btn-sm edit"><i class="fas fa-edit">Sửa</i></a>

                </td>



            </tr>

        <?php } ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </main>







    <!-- Essential javascripts for application to work-->

    <script src="../js/jquery-3.2.1.min.js"></script>

    <script src="../js/popper.min.js"></script>

    <script src="../js/bootstrap.min.js"></script>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

    <script src="../src/jquery.table2excel.js"></script>

    <script src="../js/main.js"></script>

    <!-- The javascript plugin to display page loading on top-->

    <script src="../js/plugins/pace.min.js"></script>

    <!-- Page specific javascripts-->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>

    <!-- Data table plugin-->

    <script type="text/javascript" src="../js/plugins/jquery.dataTables.min.js"></script>

    <script type="text/javascript" src="../js/plugins/dataTables.bootstrap.min.js"></script>

    <script type="text/javascript">

        $('#sampleTable').DataTable();

        //Thời Gian

    function time() {

      var today = new Date();

      var weekday = new Array(7);

      weekday[0] = "Chủ Nhật";

      weekday[1] = "Thứ Hai";

      weekday[2] = "Thứ Ba";

      weekday[3] = "Thứ Tư";

      weekday[4] = "Thứ Năm";

      weekday[5] = "Thứ Sáu";

      weekday[6] = "Thứ Bảy";

      var day = weekday[today.getDay()];

      var dd = today.getDate();

      var mm = today.getMonth() + 1;

      var yyyy = today.getFullYear();

      var h = today.getHours();

      var m = today.getMinutes();

      var s = today.getSeconds();

      m = checkTime(m);

      s = checkTime(s);

      nowTime = h + " giờ " + m + " phút " + s + " giây";

      if (dd < 10) {

        dd = '0' + dd

      }

      if (mm < 10) {

        mm = '0' + mm

      }

      today = day + ', ' + dd + '/' + mm + '/' + yyyy;

      tmp = '<span class="date"> ' + today + ' - ' + nowTime +

        '</span>';

      document.getElementById("clock").innerHTML = tmp;

      clocktime = setTimeout("time()", "1000", "Javascript");



      function checkTime(i) {

        if (i < 10) {

          i = "0" + i;

        }

        return i;

      }

    }

    </script>

    <script>

        function deleteRow(id) {

            let ans= confirm("Bạn có thật sự muốn xoá không?");

            if (ans){

                location.href= "delete-product.php?id="+id;

            }

        }

        /*jQuery(function () {

            jQuery(".trash").click(function () {

                swal({

                    title: "Cảnh báo",

                    text: "Bạn có chắc chắn là muốn xóa sản phẩm này?",

                    buttons: ["Hủy bỏ", "Đồng ý"],

                })

                    .then((willDelete) => {

                        if (willDelete) {

                            swal("Đã xóa thành công.!", {



                            });

                        }

                    });

            });

        });

        oTable = $('#sampleTable').dataTable();

        $('#all').click(function (e) {

            $('#sampleTable tbody :checkbox').prop('checked', $(this).is(':checked'));

            e.stopImmediatePropagation();

        });

        */

    </script>

</body>



</html>
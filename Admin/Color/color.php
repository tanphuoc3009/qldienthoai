<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['AD', 'NV'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy danh sách màu sắc
$sql = "SELECT MaMau, TenMau FROM MauSac";
$result = $conn->query($sql);
$mauSacs = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mauSacs[] = $row;
    }
}

// Xử lý thông tin người dùng
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . htmlspecialchars($_SESSION['user']['HoTen']) : "";
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý màu sắc</title>
    <link rel="stylesheet" href="../../Content/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../../Content/Style.css">
</head>
<body>
    <!-- Header -->
    <div id="header">
        <div class="d-flex">
            <ul class="navbar-nav ml-auto ml-md-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false" style="font-size: 20px; margin-left: 30px">
                        <i class="fas fa-bars"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="../admin.php">
                            <div class="icon" style="color: black">Báo cáo - Thống kê</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../Product/product.php">
                            <div class="icon" style="color: black">Quản lý sản phẩm</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../Brand/brand.php">
                            <div class="icon" style="color: black">Quản lý hãng sản xuất</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="color.php">
                            <div class="icon" style="color: black">Quản lý màu sắc</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../Account/account.php">
                            <div class="icon" style="color: black">Quản lý người dùng</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../Order/order.php">
                            <div class="icon" style="color: black">Quản lý đơn hàng</div>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
        <a href="../admin.php">
            <div class="logo" style="margin-left: 30px">
                <img src="../../Images/Banner/logo.jpg" alt="Logo">
            </div>
        </a>
        <div class="d-flex align-items-center ms-auto nav-icons" style="margin-right: 90px">
            <div class="user-greeting"><?php echo $nguoiDung; ?></div>
            <a href="../Account/profile.php?id=<?php echo $_SESSION['user']['MaNguoiDung']; ?>" class="icon">
                <i class="fas fa-user"></i> Hồ sơ
            </a>
            <a href="../../logout.php" class="icon">
                <i class="fas fa-sign-out"></i> Đăng xuất
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="container">
        <div class="card shadow-lg pb-4 mt-4" style="max-width: 90%; margin: 0 auto;">
            <div class="card-header brand-header text-center">
                Danh sách màu sắc
            </div>
            <div class="card-body">
                <?php if ($vaiTro == "AD"): ?>
                    <div class="d-flex justify-content-end mb-3">
                        <a href="color_create.php" class="btn1">Thêm mới</a>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center align-middle">
                        <thead class="fw-bold">
                            <tr>
                                <th style="width: 10%">STT</th>
                                <th>Mã màu</th>
                                <th>Tên màu</th>
                                <?php if ($vaiTro == "AD"): ?>
                                    <th style="width: 25%">Thao tác</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 0; ?>
                            <?php foreach ($mauSacs as $item): ?>
                                <tr>
                                    <td><?php echo ++$i; ?></td>
                                    <td><?php echo htmlspecialchars($item['MaMau']); ?></td>
                                    <td><?php echo htmlspecialchars($item['TenMau']); ?></td>
                                    <?php if ($vaiTro == "AD"): ?>
                                        <td class="d-flex justify-content-center gap-2">
                                            <a href="color_edit.php?id=<?php echo htmlspecialchars($item['MaMau']); ?>" 
                                               class="btn2">
                                                Sửa
                                            </a>
                                            <button type="button" class="btn btn-danger delete-color-btn" 
                                                    data-mamau="<?php echo htmlspecialchars($item['MaMau']); ?>" 
                                                    data-tenmau="<?php echo htmlspecialchars($item['TenMau']); ?>">
                                                Xóa
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="text-end" style="margin-right: 26px">
                <a href="../admin.php">Về trang chủ</a>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận xóa -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-center" style="background: red; color: white;">
                    <div class="modal-title" id="deleteModalLabel" 
                         style="font-weight: bold; font-size: 20px; align-content: center;">
                        Xác nhận xóa màu sắc
                    </div>
                </div>
                <div class="modal-body">
                    <div id="deleteMessage" class="alert alert-danger text-center" style="display: none;"></div>
                    <p class="text-center" style="color: red; font-weight: bold;">
                        Bạn có chắc chắn muốn xóa màu sắc <strong id="modalTenMau"></strong> không?
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn2" data-dismiss="modal">Quay lại</button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Xóa</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function() {
            // Xử lý xóa màu sắc
            $('.delete-color-btn').click(function() {
                var maMau = $(this).data('mamau');
                var tenMau = $(this).data('tenmau');
                $('#modalTenMau').text(tenMau);
                $('#confirmDeleteBtn').data('mamau', maMau);
                $('#deleteMessage').hide().text('');
                $('#confirmDeleteBtn').prop('disabled', false);
                $('#deleteModal').modal('show');
            });

            // Xử lý khi bấm nút Xác nhận xóa
            $('#confirmDeleteBtn').click(function() {
                var maMau = $(this).data('mamau');
                $.ajax({
                    url: 'color_delete.php',
                    type: 'POST',
                    data: {
                        confirm_delete: true,
                        id: maMau
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Xóa thành công thì reload trang
                            alert(response.message);
                            location.reload();
                        } else {
                            // Hiển thị lỗi trong modal
                            $('#deleteMessage').text(response.message).show();
                            $('#confirmDeleteBtn').prop('disabled', true);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#deleteMessage').text('Đã xảy ra lỗi: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không thể kết nối server!')).show();
                        $('#confirmDeleteBtn').prop('disabled', true);
                    }
                });
            });
        });
    </script>
</body>
</html>
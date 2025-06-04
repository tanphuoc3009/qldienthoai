<?php
require 'config.php';

// Kiểm tra nếu người dùng đã đăng nhập
if (isset($_SESSION['user'])) {
    // Đăng xuất: Xóa tất cả dữ liệu phiên
    $_SESSION = [];
    session_destroy();
    // Khởi tạo lại phiên để hiển thị thông báo
    session_start();
} else {
    // Khởi tạo biến thông báo
    $tb = '';
}

// Xử lý form đăng nhập
if (isset($_POST['login'])) {
    $email = $_POST['Email'];
    $matKhau = $_POST['MatKhau'];

    // Kiểm tra người dùng
    $sql = "SELECT MaNguoiDung, Email, HoTen, DiaChi, VaiTro FROM NguoiDung WHERE Email = ? AND MatKhau = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }

    $stmt->bind_param("ss", $email, $matKhau);
    if (!$stmt->execute()) {
        die("Lỗi thực thi truy vấn: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // Lưu thông tin người dùng vào session
        $_SESSION['user'] = $result->fetch_assoc();
        $stmt->close();

        // Chuyển hướng theo vai trò
        if ($_SESSION['user']['VaiTro'] === 'KH') {
            header("Location: index.php");
        } else {
            header("Location: Admin/admin.php");
        }
        exit;
    } else {
        $tb = "Email hoặc mật khẩu không đúng!";
        $_SESSION['user'] = null;
    }
    $stmt->close();
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="Content/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="Content/Style.css">
</head>
<body>
    <!-- Header -->
    <div id="header">
        <a href="index.php">
            <div class="logo">
                <img src="Images/Banner/logo.jpg" alt="Logo">
            </div>
        </a>
        <a href="index.php">
            <h2 class="site-title">Siêu thị điện thoại Ego Mobile</h2>
        </a>
        <div class="nav-icons" style="margin-right: 90px">
            <a href="login.php" class="icon">
                <i class="fas fa-user"></i> Đăng nhập
            </a>
            <a href="Customer/Account/register.php" class="icon">
                <i class="fas fa-cog"></i> Đăng ký
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="container mt-5">
        <div class="row">
            <!-- Banner -->
            <div class="col-md-7 mb-4">
                <div class="h-100">
                    <img src="Images/Banner/banner.jpg" class="img-fluid w-100 h-100 object-fit-cover" alt="Banner">
                </div>
            </div>
            <div class="col-md-1"></div>
            <!-- Form đăng nhập -->
            <div class="col-md-4 d-flex justify-content-center align-items-center">
                <div class="card shadow-lg" style="width: 100%; border-radius: 10px;">
                    <div class="card-header brand-header text-center">
                        Đăng nhập
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($tb)): ?>
                            <div class="alert alert-danger text-center"><?php echo $tb; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="login.php">
                            <table class="table1">
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>
                                        <input type="text" name="Email" class="form-control" required
                                               placeholder="Nhập email" style="border-radius: 5px"
                                               value="<?php echo isset($_POST['Email']) ?  $_POST['Email'] : ''?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Mật khẩu:</strong></td>
                                    <td>
                                        <input type="password" name="MatKhau" class="form-control" required
                                               placeholder="Nhập mật khẩu" style="border-radius: 5px"
                                               value="<?php echo isset($_POST['MatKhau']) ?  $_POST['MatKhau'] : ''?>">
                                    </td>
                                </tr>
                            </table>
                            <div class="form-group mt-3">
                                <div class="text-center d-flex justify-content-center gap-2">
                                    <a href="Customer/Account/register.php" class="btn1">
                                        Đăng ký
                                    </a>
                                    <input type="submit" name="login" value="Đăng nhập" class="btn2">
                                </div>
                            </div>
                            <div class="text-center d-flex justify-content-center mt-3">
                                <a href="forgot_password.php">Quên mật khẩu?</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>
</body>
</html>

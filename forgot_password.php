<?php
require 'config.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Khởi tạo biến thông báo
$tb = '';

// Xử lý form
if (isset($_POST['submit'])) {
    $email = $_POST['email'];

    // Kiểm tra email tồn tại
    $sql = "SELECT MaNguoiDung, Email FROM NguoiDung WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $tb = "Email không tồn tại trong hệ thống!";
            $stmt->close();
        } else {
            $user = $result->fetch_assoc();
            $stmt->close();

            // Tạo mật khẩu mới
            $matKhauMoi = substr(bin2hex(random_bytes(4)), 0, 8);

            // Cập nhật mật khẩu
            $sql = "UPDATE NguoiDung SET MatKhau = ? WHERE Email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $matKhauMoi, $email);
            if ($stmt->execute()) {
                $stmt->close();

                // Gửi email
                $mail = new PHPMailer(true);
                try {
                    // Cấu hình SMTP
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'thcsmeomeomeo@gmail.com';
                    $mail->Password = $app_password;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->CharSet = 'UTF-8';

                    // Thiết lập email
                    $mail->setFrom('thcsmeomeomeo@gmail.com', 'No-reply');
                    $mail->addAddress($email);
                    $mail->Subject = "Ego Mobile - Khôi phục mật khẩu";
                    $mail->Body = "Mật khẩu mới của bạn là: $matKhauMoi.\nVui lòng đổi mật khẩu sau khi đăng nhập lại!";

                    $mail->send();
                    $tb = "Mật khẩu mới đã được gửi đến email của bạn!";
                } catch (Exception $e) {
                    $tb = "Gửi email thất bại. Vui lòng thử lại sau!";
                }
            } else {
                $tb = "Lỗi khi cập nhật mật khẩu.";
                $stmt->close();
            }
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu</title>
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
    <div class="container">
        <div class="card shadow-lg mt-4" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header brand-header text-center">
                Khôi phục mật khẩu
            </div>
            <div class="card-body">
                <?php if (!empty($tb)): ?>
                    <div class="alert <?php echo $tb == 'Mật khẩu mới đã được gửi đến email của bạn!' ? 'alert-success' : 'alert-danger'; ?>" 
                         style="text-align: center">
                        <?php echo $tb; ?>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-center">
                    <form method="POST">
                        <table class="table1" cellpadding="8">
                            <tr>
                                <td>
                                    <label for="email" class="form-label fw-bold mb-0">Nhập email của bạn:</label>
                                </td>
                                <td>
                                    <input type="email" name="email" id="email" class="form-control" 
                                           required style="min-width: 300px;"
                                           value="<?php echo isset($_POST['email']) ?  $_POST['email'] : ''?>">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-center pt-3">
                                    <button type="submit" name="submit" class="btn2">
                                        Gửi mật khẩu mới
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-center">
                                    <a href="login.php">Quay lại đăng nhập</a>
                                </td>
                            </tr>
                        </table>
                    </form>
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

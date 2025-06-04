<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy thông tin người dùng
$nguoiDung = $_SESSION['user'];

// Xử lý callback từ MoMo
$resultCode = $_GET['resultCode'];
$orderId = $_GET['orderId'];
$message = $_GET['message'];

// Kiểm tra nếu thanh toán thành công
if ($resultCode === '0') {
    // Cập nhật trạng thái đơn hàng
    $sql = "UPDATE DonDatHang SET ThanhToan = 1 WHERE MaDDH = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $stmt->close();
    $tb = "Thanh toán thành công đơn hàng $orderId!";
    $tbColor = "green";
} else {
    $tb = "Thanh toán thất bại: $message";
    $tbColor = "red";
}

// Xóa dữ liệu giỏ hàng
$_SESSION['SLMatHang'] = null;
$_SESSION['GioHang'] = null;

// Số lượng mặt hàng trong giỏ
$slMatHang = isset($_SESSION['SLMatHang']) ? $_SESSION['SLMatHang'] : 0;

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán MoMo</title>
    <link rel="stylesheet" href="../../Content/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../../Content/Style.css">
</head>
<body>
    <!-- Header -->
    <div id="header">
        <a href="../../index.php">
            <div class="logo">
                <img src="../../Images/Banner/logo.jpg" alt="Logo">
            </div>
        </a>
        <div class="nav-icons" style="margin-right: 90px">
            <div class="user-greeting">Xin chào, <?php echo htmlspecialchars($nguoiDung['HoTen']); ?></div>
            <a href="cart.php" class="icon">
                <i class="fas fa-shopping-cart"></i> Giỏ hàng(<span id="SLMatHang"><?php echo $slMatHang; ?></span>)
            </a>
            <a href="../Order/order.php" class="icon">
                <i class="fas fa-receipt"></i> Đơn hàng
            </a>
            <a href="../Account/profile.php?id=<?php echo $nguoiDung['MaNguoiDung']; ?>" class="icon">
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
                Kết quả thanh toán MoMo
            </div>
            <div class="card-body">
                <div class="text-center mt-3">
                    <h4 class="mb-4">
                        <span style="color: <?php echo $tbColor; ?>"><?php echo htmlspecialchars($tb); ?></span>
                        <?php if ($tbColor === 'red'): ?>
                            <br>
                            <span style="color: red">Vui lòng liên hệ bộ phận CSKH để được hỗ trợ!</span>
                        <?php endif; ?>
                    </h4>
                    <div class="text-center d-flex justify-content-center gap-2">
                        <a href="../Order/order.php" class="btn1">Danh sách đơn hàng</a>
                        <a href="../../index.php" class="btn2">Tiếp tục mua sắm</a>
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
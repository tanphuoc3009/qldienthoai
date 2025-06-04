<?php
require ('../../config.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

$id = $_GET['id'];

// Kiểm tra id
if (empty($id)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Kiểm tra id khớp với người dùng hiện tại
if ($id !== $_SESSION['user']['MaNguoiDung']) {
header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy thông tin người dùng
$sql = "SELECT MaNguoiDung, HoTen, GioiTinh, SDT, Email, MatKhau, DiaChi, AnhDaiDien 
        FROM NguoiDung WHERE MaNguoiDung = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
$stmt->bind_param("s", $id);
if (!$stmt->execute()) {
    die("Lỗi thực thi truy vấn: " . $stmt->error);
}
$result = $stmt->get_result();
$nguoiDung = $result->fetch_assoc();
$stmt->close();

// Chuyển đổi giới tính
$gioiTinh = $nguoiDung['GioiTinh'] ? "Nam" : "Nữ";

// Số lượng mặt hàng trong giỏ
$slMatHang = isset($_SESSION['SLMatHang']) ? $_SESSION['SLMatHang'] : 0;

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin người dùng</title>
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
            <div class="user-greeting"><?php echo "Xin chào, " . $_SESSION['user']['HoTen'] ?></div>
            <a href="../Cart/cart.php" class="icon">
                <i class="fas fa-shopping-cart"></i> Giỏ hàng(<span id="SLMatHang"><?php echo $slMatHang; ?></span>)
            </a>
            <a href="../Order/order.php" class="icon">
                <i class="fas fa-receipt"></i> Đơn hàng
            </a>
            <a href="profile.php?id=<?php echo $_SESSION['user']['MaNguoiDung'] ?>" class="icon">
                <i class="fas fa-user"></i> Hồ sơ
            </a>
            <a href="../../logout.php" class="icon">
                <i class="fas fa-sign-out"></i> Đăng xuất
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="container mt-4">
        <div class="card shadow-lg pb-4" style="max-width: 70%; margin: 0 auto;">
            <div class="card-header brand-header text-center">
                Thông tin người dùng
            </div>
            <div class="row p-4">
                <!-- Ảnh đại diện -->
                <div class="col-md-4 d-flex flex-column align-items-center justify-content-center">
                    <img src="../../Images/User/<?php echo $nguoiDung['AnhDaiDien']; ?>" 
                         alt="<?php echo $nguoiDung['HoTen']; ?>" 
                         class="img-fluid rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
                </div>
                <!-- Thông tin người dùng -->
                <div class="col-md-8">
                    <h2 class="text-center"><?php echo $nguoiDung['HoTen']; ?></h2>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item" style="font-size: 18px;">
                            <strong>Mã người dùng:</strong> <?php echo $nguoiDung['MaNguoiDung']; ?>
                        </li>
                        <li class="list-group-item" style="font-size: 18px;">
                            <strong>Giới tính:</strong> <?php echo $gioiTinh; ?>
                        </li>
                        <li class="list-group-item" style="font-size: 18px;">
                            <strong>Số điện thoại:</strong> <?php echo $nguoiDung['SDT']; ?>
                        </li>
                        <li class="list-group-item" style="font-size: 18px;">
                            <strong>Email:</strong> <?php echo $nguoiDung['Email']; ?>
                        </li>
                        <li class="list-group-item" style="font-size: 18px;">
                            <strong>Mật khẩu:</strong> <?php echo $nguoiDung['MatKhau']; ?>
                        </li>
                        <li class="list-group-item" style="font-size: 18px;">
                            <strong>Địa chỉ:</strong> <?php echo $nguoiDung['DiaChi']; ?>
                        </li>
                    </ul>
                    <div class="text-center d-flex justify-content-center gap-2 mt-4">
                        <a href="../../index.php" class="btn1">Về trang chủ</a>
                        <a href="profile_edit.php?id=<?php echo $id ?>" class="btn2">Chỉnh sửa</a>
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

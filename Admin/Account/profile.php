<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['AD', 'NV'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

$id = isset($_GET['id']) ? $_GET['id'] : null;

// Kiểm tra id
if (empty($id)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Kiểm tra quyền truy cập
$user = $_SESSION['user'];
if ($user['VaiTro'] === 'NV' && $id !== $user['MaNguoiDung']) {
    header("HTTP/1.1 404 Not Found");
    die("Không tìm thấy trang!");
}

// Lấy thông tin người dùng
$sql = "SELECT MaNguoiDung, HoTen, GioiTinh, SDT, Email, MatKhau, DiaChi, AnhDaiDien, VaiTro 
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

if (!$nguoiDung) {
    header("HTTP/1.1 404 Not Found");
    die("Không tìm thấy trang!");
}

// Chuyển đổi giới tính và vai trò
$gioiTinh = $nguoiDung['GioiTinh'] ? "Nam" : "Nữ";
$quyen = $nguoiDung['VaiTro'] === 'KH' ? "Khách hàng" : ($nguoiDung['VaiTro'] === 'NV' ? "Nhân viên" : "Admin");

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
                        <a class="dropdown-item" href="../Color/color.php">
                            <div class="icon" style="color: black">Quản lý màu sắc</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="account.php">
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
            <div class="user-greeting"><?php echo "Xin chào, " . $user['HoTen']; ?></div>
            <a href="profile.php?id=<?php echo $user['MaNguoiDung']; ?>" class="icon">
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
                        <?php if ($user['MaNguoiDung'] === $nguoiDung['MaNguoiDung']): ?>
                            <li class="list-group-item" style="font-size: 18px;">
                                <strong>Mật khẩu:</strong> <?php echo $nguoiDung['MatKhau']; ?>
                            </li>
                        <?php endif; ?>
                        <li class="list-group-item" style="font-size: 18px;">
                            <strong>Địa chỉ:</strong> <?php echo $nguoiDung['DiaChi']; ?>
                        </li>
                        <li class="list-group-item" style="font-size: 18px;">
                            <strong>Vai trò:</strong> <?php echo $quyen; ?>
                        </li>
                    </ul>
                    <?php if ($user['MaNguoiDung'] === $nguoiDung['MaNguoiDung']): ?>
                        <div class="text-center d-flex justify-content-center gap-2 mt-4">
                            <a href="../admin.php" class="btn1">Về trang chủ</a>
                            <a href="profile_edit.php?id=<?php echo urlencode($id); ?>" class="btn2">Chỉnh sửa</a>
                        </div>
                    <?php else: ?>
                        <div class="text-center d-flex justify-content-center gap-2 mt-4">
                            <a href="account.php" class="btn2">Quay lại</a>
                        </div>
                    <?php endif; ?>
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
</body>
</html>

<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Hàm tạo mã người dùng tự động
function layMaUser($conn) {
    $sql = "SELECT MaNguoiDung FROM NguoiDung ORDER BY MaNguoiDung DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['MaNguoiDung'];
        $num = (int)substr($lastId, 4) + 1;
        return 'USER' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
    return 'USER001';
}

// Xử lý form POST
$tb = '';
$maNguoiDung = layMaUser($conn);
if (isset($_POST['create'])) {
    // Lấy dữ liệu
    $hoTen = isset($_POST['HoTen']) ? $_POST['HoTen'] : '';
    $gioiTinh = isset($_POST['GioiTinh']) && $_POST['GioiTinh'] === '1' ? 1 : 0;
    $sdt = isset($_POST['SDT']) ? $_POST['SDT'] : '';
    $email = isset($_POST['Email']) ? $_POST['Email'] : '';
    $matKhau = isset($_POST['MatKhau']) ? $_POST['MatKhau'] : '';
    $diaChi = isset($_POST['DiaChi']) ? $_POST['DiaChi'] : '';
    $vaiTro = isset($_POST['VaiTro']) ? $_POST['VaiTro'] : 'KH';
    $anhDaiDien = '';

    // Xử lý ảnh đại diện
    $fileName = basename($_FILES['Avatar']['name']);
    $uploadDir = '../../Images/User/';
    $uploadPath = $uploadDir . $fileName;

    // Kiểm tra loại file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($_FILES['Avatar']['tmp_name']);
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['Avatar']['tmp_name'], $uploadPath)) {
            $anhDaiDien = $fileName;
        } else {
            $tb = "Lỗi khi lưu ảnh đại diện!";
        }
    } else {
        $tb = "Chỉ chấp nhận file ảnh JPEG, PNG hoặc GIF!";
    }

    // Kiểm tra email đã tồn tại
    $sql = "SELECT Email FROM NguoiDung WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $tb = "Email đã tồn tại! Vui lòng chọn email khác.";
        $stmt->close();
    } elseif (!str_ends_with($email, '@gmail.com')) {
        $tb = "Vui lòng sử dụng email có đuôi @gmail.com!";
        $stmt->close();
    } else {
        $stmt->close();

        // Lưu người dùng
        $sql = "INSERT INTO NguoiDung (MaNguoiDung, HoTen, GioiTinh, SDT, Email, MatKhau, DiaChi, AnhDaiDien, VaiTro) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $tb = "Lỗi chuẩn bị truy vấn: " . $conn->error;
        } else {
            $stmt->bind_param("ssissssss", $maNguoiDung, $hoTen, $gioiTinh, $sdt, $email, $matKhau, $diaChi, $anhDaiDien, $vaiTro);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: account.php");
                exit;
            } else {
                $tb = "Lỗi khi thêm người dùng: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Xử lý thông tin người dùng
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . htmlspecialchars($_SESSION['user']['HoTen']) : "";

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa ảnh sản phẩm</title>
    <link rel="stylesheet" href="../../Content/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../../Content/Style.css">
</head>
<body>
    <!-- Header -->
    <div id="header">
        <div class="d-flex">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="true" style="font-size: 20px; margin-left: 30px">
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
    <div class="container mt-4 d-flex justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg pb-4">
                <div class="card-header brand-header text-center">
                    Thêm người dùng
                </div>
                <div class="card-body col-md-10 mx-auto">
                    <?php if (!empty($tb)): ?>
                        <div class="alert alert-danger text-center">
                            <?php echo htmlspecialchars($tb); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <table class="table1">
                            <tr>
                                <td class="col-md-3"><strong>Mã người dùng:</strong></td>
                                <td>
                                    <input type="text" value="<?php echo $maNguoiDung; ?>" 
                                           disabled readonly class="form-control" />
                                    <input type="hidden" name="MaNguoiDung" value="<?php echo $maNguoiDung; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Họ và tên:</strong></td>
                                <td>
                                    <input type="text" name="HoTen" class="form-control" required 
                                           value="<?php echo isset($_POST['HoTen']) ? htmlspecialchars($_POST['HoTen']) : ''; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Giới tính:</strong></td>
                                <td>
                                    <input type="radio" name="GioiTinh" value="1" 
                                           <?php echo (!isset($_POST['GioiTinh']) || $_POST['GioiTinh'] === '1') ? 'checked' : ''; ?>> Nam
                                    <input type="radio" name="GioiTinh" value="0" 
                                           <?php echo (isset($_POST['GioiTinh']) && $_POST['GioiTinh'] === '0') ? 'checked' : ''; ?>> Nữ
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Số điện thoại:</strong></td>
                                <td>
                                    <input type="text" name="SDT" class="form-control" required 
                                           pattern="\d{10}" title="Số điện thoại phải có 10 chữ số." 
                                           value="<?php echo isset($_POST['SDT']) ? htmlspecialchars($_POST['SDT']) : ''; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>
                                    <input type="email" name="Email" class="form-control" required 
                                           value="<?php echo isset($_POST['Email']) ? htmlspecialchars($_POST['Email']) : ''; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Mật khẩu:</strong></td>
                                <td>
                                    <input type="password" name="MatKhau" class="form-control" required 
                                           minlength="6" title="Mật khẩu phải có ít nhất 6 ký tự." 
                                           value="<?php echo isset($_POST['MatKhau']) ? htmlspecialchars($_POST['MatKhau']) : ''; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Địa chỉ:</strong></td>
                                <td>
                                    <input type="text" name="DiaChi" class="form-control" 
                                           value="<?php echo isset($_POST['DiaChi']) ? htmlspecialchars($_POST['DiaChi']) : ''; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Ảnh đại diện:</strong></td>
                                <td>
                                    <input type="file" name="Avatar" accept="image/*" required style="height: 30px;" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Vai trò:</strong></td>
                                <td>
                                    <input type="radio" name="VaiTro" value="KH" 
                                           <?php echo (!isset($_POST['VaiTro']) || $_POST['VaiTro'] == 'KH') ? 'checked' : ''; ?>> Khách hàng&nbsp;
                                    <input type="radio" name="VaiTro" value="NV" 
                                           <?php echo (isset($_POST['VaiTro']) && $_POST['VaiTro'] == 'NV') ? 'checked' : ''; ?>> Nhân viên
                                </td>
                            </tr>
                        </table>
                        <div class="form-group mt-3">
                            <div class="text-center d-flex justify-content-center gap-2">
                                <a href="account.php" class="btn1">Quay lại</a>
                                <input type="submit" name="create" value="Thêm" class="btn2" />
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
    mysqli_close($conn);
?>
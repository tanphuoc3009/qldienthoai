<?php
require('../../config.php');

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

// Kiểm tra nếu người dùng đã đăng nhập
if (isset($_SESSION['user'])) {
    // Đăng xuất: Xóa tất cả dữ liệu phiên
    $_SESSION = [];
    session_destroy();
    // Khởi tạo lại phiên để hiển thị thông báo
    session_start();
} else {
    // Khởi tạo biến
    $tb = '';
    $maNguoiDung = layMaUser($conn);
    $vaiTro = 'KH';
}

// Xử lý form đăng ký
if (isset($_POST['register'])) {
    $hoTen = $_POST['HoTen'];
    $gioiTinh = $_POST['GioiTinh'];
    $sdt = $_POST['SDT'];
    $email = $_POST['Email'];
    $matKhau = $_POST['MatKhau'];
    $diaChi = $_POST['DiaChi'];
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
        $gioiTinhBool = $gioiTinh === 'True' ? 1 : 0;
        $stmt->bind_param("ssissssss", $maNguoiDung, $hoTen, $gioiTinhBool, $sdt, $email, $matKhau, $diaChi, $anhDaiDien, $vaiTro);

        if ($stmt->execute()) {
            // Lưu thông tin người dùng vào session
            $_SESSION['user'] = [
                'MaNguoiDung' => $maNguoiDung,
                'HoTen' => $hoTen,
                'Email' => $email,
                'DiaChi' => $diaChi,
                'VaiTro' => $vaiTro
            ];
            $stmt->close();
            // Chuyển hướng đến hồ sơ
            header("Location: profile.php?id=" . $maNguoiDung);
            exit;
        } else {
            $tb = "Lỗi khi đăng ký: " . $stmt->error;
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
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
        <a href="../../index.php">
            <h2 class="site-title">Siêu thị điện thoại Ego Mobile</h2>
        </a>
        <div class="nav-icons" style="margin-right: 90px">
            <a href="../../login.php" class="icon">
                <i class="fas fa-user"></i> Đăng nhập
            </a>
            <a href="register.php" class="icon">
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
                    <img src="../../Images/Banner/banner.jpg" class="img-fluid w-100 h-100 object-fit-cover" alt="Banner">
                </div>
            </div>
            <!-- Form đăng ký -->
            <div class="col-md-5 d-flex justify-content-center align-items-center">
                <div class="card shadow-lg" style="width: 100%; border-radius: 10px;">
                    <div class="card-header brand-header text-center">
                        Đăng ký
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($tb)): ?>
                            <div class="alert alert-danger text-center"><?php echo $tb; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="register.php" enctype="multipart/form-data">
                            <input type="hidden" name="MaNguoiDung" value="<?php echo $maNguoiDung; ?>">
                            <input type="hidden" name="VaiTro" value="<?php echo $vaiTro; ?>">
                            <table class="table1">
                                <tr>
                                    <td><strong>Họ và tên:</strong></td>
                                    <td>
                                        <input type="text" name="HoTen" class="form-control" 
                                            style="border-radius: 5px" 
                                            value="<?php echo isset($_POST['HoTen']) ? $_POST['HoTen'] : ''; ?>" required>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Giới tính:</strong></td>
                                    <td>
                                        <div class="checkbox">
                                            <label><input type="radio" name="GioiTinh" value="True" checked <?php echo (isset($_POST['GioiTinh']) && $_POST['GioiTinh'] == 'True') ? 'checked' : ''; ?>> Nam</label>
                                            &nbsp;
                                            <label><input type="radio" name="GioiTinh" value="False" <?php echo (isset($_POST['GioiTinh']) && $_POST['GioiTinh'] == 'False') ? 'checked' : ''; ?>> Nữ</label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Số điện thoại:</strong></td>
                                    <td>
                                        <input type="text" name="SDT" class="form-control" 
                                            style="border-radius: 5px" 
                                            value="<?php echo isset($_POST['SDT']) ? $_POST['SDT'] : ''; ?>" required 
                                            pattern="^\d{10}$" title="Số điện thoại phải có 10 chữ số.">
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>
                                        <input type="email" name="Email" class="form-control" 
                                            style="border-radius: 5px" 
                                            value="<?php echo isset($_POST['Email']) ? $_POST['Email'] : ''; ?>" required>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Mật khẩu:</strong></td>
                                    <td>
                                        <input type="password" name="MatKhau" class="form-control" 
                                            style="border-radius: 5px" required 
                                            minlength="6" title="Mật khẩu phải có ít nhất 6 ký tự.">
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Địa chỉ:</strong></td>
                                    <td>
                                        <input type="text" name="DiaChi" class="form-control" 
                                            style="border-radius: 5px" 
                                            value="<?php echo isset($_POST['DiaChi']) ? $_POST['DiaChi'] : ''; ?>" required>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ảnh đại diện:</strong></td>
                                    <td>
                                        <input type="file" name="Avatar" accept="image/*">
                                    </td>
                                </tr>
                            </table>
                            <div class="form-group mt-3">
                                <div class="text-center d-flex justify-content-center gap-2">
                                    <a href="../../login.php" class="btn1">
                                        Đăng nhập
                                    </a>
                                    <input type="submit" name="register" value="Đăng ký" class="btn2">
                                </div>
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
<?php
    mysqli_close($conn);
?>
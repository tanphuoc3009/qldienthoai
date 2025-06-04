<?php
require ('../../config.php');

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Hàm tạo mã sản phẩm tự động
function layMaSP($conn) {
    $sql = "SELECT MaSP FROM SanPham ORDER BY MaSP DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['MaSP'];
        $num = (int)substr($lastId, 2) + 1;
        return 'SP' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
    return 'SP001';
}

// Lấy danh sách hãng sản xuất và màu sắc
$hangSanXuats = [];
$result = mysqli_query($conn, "SELECT MaHSX, TenHSX FROM HangSanXuat");
while ($row = mysqli_fetch_assoc($result)) {
    $hangSanXuats[$row['MaHSX']] = $row['TenHSX'];
}

$mauSacs = [];
$result = mysqli_query($conn, "SELECT MaMau, TenMau FROM MauSac");
while ($row = mysqli_fetch_assoc($result)) {
    $mauSacs[$row['MaMau']] = $row['TenMau'];
}

// Xử lý form POST
$tb = '';
if (isset($_POST['create'])) {
    // Lấy dữ liệu
    $tenSP = $_POST['TenSP'];
    $giaBan = (float)$_POST['GiaBan'];
    $mota = $_POST['Mota'];
    $ram = (int)$_POST['Ram'];
    $dungLuong = (int)$_POST['DungLuong'];
    $heDieuHanh = $_POST['HeDieuHanh'];
    $maHSX = $_POST['MaHSX'];
    $maMau = $_POST['MaMau'];
    $anhBia = '';

    // Xử lý file ảnh
    $fileName = basename($_FILES['Avatar']['name']);
    $uploadDir = '../../Images/SanPham/';
    $uploadPath = $uploadDir . $fileName;

    // Kiểm tra loại file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($_FILES['Avatar']['tmp_name']);
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['Avatar']['tmp_name'], $uploadPath)) {
            $anhBia = $fileName;
        } else {
            $tb = "Lỗi khi lưu ảnh sản phẩm!";
        }
    } else {
        $tb = "Chỉ chấp nhận file ảnh JPEG, PNG hoặc GIF!";
    }

    // Lưu sản phẩm
    $maSP = layMaSP($conn);
    $sql = "INSERT INTO SanPham (MaSP, TenSP, GiaBan, Mota, Ram, DungLuong, HeDieuHanh, MaHSX) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $tb = "Lỗi chuẩn bị truy vấn: " . $conn->error;
    } else {
        $stmt->bind_param("ssdsiiss", $maSP, $tenSP, $giaBan, $mota, $ram, $dungLuong, $heDieuHanh, $maHSX);
        if ($stmt->execute()) {
            // Lưu chi tiết sản phẩm
            $sql2 = "INSERT INTO ChiTietSanPham (MaSP, MaMau, AnhBia) VALUES (?, ?, ?)";
            $stmt2 = $conn->prepare($sql2);
            if (!$stmt2) {
                $tb = "Lỗi chuẩn bị truy vấn chi tiết: " . $conn->error;
            } else {
                $stmt2->bind_param("sss", $maSP, $maMau, $anhBia);
                if ($stmt2->execute()) {
                    $stmt2->close();
                    header("Location: product.php");
                    exit;
                } else {
                    $tb = "Lỗi khi thêm chi tiết sản phẩm: " . $stmt2->error;
                    $stmt2->close();
                }
            }
        } else {
            $tb = "Lỗi khi thêm sản phẩm: " . $stmt->error;
        }
        $stmt->close();
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
    <title>Thêm sản phẩm</title>
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
                        <a class="dropdown-item" href="product.php">
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
    <div class="container mt-4 d-flex justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg pb-4">
                <div class="card-header brand-header text-center">
                    Thêm sản phẩm
                </div>
                <div class="card-body col-md-8 mx-auto">
                    <?php if (!empty($tb)): ?>
                        <div class="alert alert-danger">
                            <?php echo $tb; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <table class="table1">
                            <tr>
                                <td class="col-md-3"><strong>Mã sản phẩm:</strong></td>
                                <td>
                                    <input type="text" value="<?php echo layMaSP($conn); ?>" 
                                            disabled readonly class="form-control" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Tên sản phẩm:</strong></td>
                                <td>
                                    <input type="text" name="TenSP" class="form-control" required 
                                            value="<?php echo isset($_POST['TenSP']) ? $_POST['TenSP'] : ''; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Giá bán:</strong></td>
                                <td>
                                    <input type="number" name="GiaBan" class="form-control" required min="0"
                                            value="<?php echo isset($_POST['GiaBan']) ? $_POST['GiaBan'] : ''; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Mô tả:</strong></td>
                                <td>
                                    <textarea name="Mota" class="form-control" rows="4"><?php echo isset($_POST['Mota']) ? $_POST['Mota'] : ''; ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>RAM:</strong></td>
                                <td>
                                    <input type="number" name="Ram" class="form-control" required min="0"
                                            value="<?php echo isset($_POST['Ram']) ? $_POST['Ram'] : ''; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Dung lượng:</strong></td>
                                <td>
                                    <input type="number" name="DungLuong" class="form-control" required min="0"
                                            value="<?php echo isset($_POST['DungLuong']) ? $_POST['DungLuong'] : ''; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Hệ điều hành:</strong></td>
                                <td>
                                    <input type="text" name="HeDieuHanh" class="form-control" required
                                            value="<?php echo isset($_POST['HeDieuHanh']) ? $_POST['HeDieuHanh'] : ''; ?>" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Hãng sản xuất:</strong></td>
                                <td>
                                    <select name="MaHSX" class="form-control" required>
                                        <option value="">-- Chọn hãng sản xuất --</option>
                                        <?php
                                        foreach ($hangSanXuats as $key => $value) {
                                            $selectedHSX = ($key == $viewData['MaHSX']) ? 'selected' : '';
                                            echo "<option value='$key' $selected>" . $value . "</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Màu sắc:</strong></td>
                                <td>
                                    <select name="MaMau" class="form-control" required>
                                        <option value="">-- Chọn màu sắc --</option>
                                        <?php
                                        foreach ($mauSacs as $key => $value) {
                                            $selectedMau = ($key == $viewData['MaMau']) ? 'selected' : '';
                                            echo "<option value='$key' $selected>" . $value . "</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Ảnh bìa:</strong></td>
                                <td>
                                    <input type="file" name="Avatar" accept="image/*" required style="height: 30px;" />
                                </td>
                            </tr>
                        </table>
                        <div class="form-group mt-3">
                            <div class="text-center d-flex justify-content-center gap-2">
                                <a href="product.php" class="btn1">Quay lại</a>
                                <input type="submit" name="create" value="Thêm mới" class="btn2" />
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
    mysqli_close($conn);
?>

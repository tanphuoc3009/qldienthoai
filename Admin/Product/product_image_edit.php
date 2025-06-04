<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

$id = isset($_GET['id']) ? $_GET['id'] : null;
$maMau = isset($_GET['maMau']) ? $_GET['maMau'] : null;

// Kiểm tra id và maMau
if (empty($id) || empty($maMau)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy thông tin chi tiết sản phẩm
$sql = "SELECT ct.MaSP, ct.MaMau, ct.AnhBia, sp.TenSP, ms.TenMau 
        FROM ChiTietSanPham ct 
        JOIN SanPham sp ON ct.MaSP = sp.MaSP 
        JOIN MauSac ms ON ct.MaMau = ms.MaMau 
        WHERE ct.MaSP = ? AND ct.MaMau = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
$stmt->bind_param("ss", $id, $maMau);
if (!$stmt->execute()) {
    die("Lỗi thực thi truy vấn: " . $stmt->error);
}
$result = $stmt->get_result();
$chiTiet = $result->fetch_assoc();
$stmt->close();

if (!$chiTiet) {
    header("HTTP/1.1 404 Not Found");
    die("Không tìm thấy chi tiết sản phẩm!");
}

// Xử lý form POST
$tb = '';
if (isset($_POST['edit'])) {
    $maSP = $id;
    $maMau = $_POST['MaMau'];
    $anhBia = $chiTiet['AnhBia'];

    // Xử lý file ảnh
    if (isset($_FILES['Avatar']) && $_FILES['Avatar']['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['Avatar']['name']);
        $uploadDir = '../../Images/SanPham/';
        $uploadPath = $uploadDir . $fileName;

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
    }

    // Cập nhật ảnh bìa
    if (empty($tb)) {
        $sql = "UPDATE ChiTietSanPham SET AnhBia = ? WHERE MaSP = ? AND MaMau = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $tb = "Lỗi chuẩn bị truy vấn: " . $conn->error;
        } else {
            $stmt->bind_param("sss", $anhBia, $maSP, $maMau);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: product_details.php?id=" . $maSP . "&action=edit");
                exit;
            } else {
                $tb = "Lỗi khi cập nhật ảnh: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Xử lý thông tin người dùng
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . htmlspecialchars($_SESSION['user']['HoTen']) : "";

mysqli_close($conn);
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
            <a href="../Account/profile.php?id=<?php echo urlencode($_SESSION['user']['MaNguoiDung']); ?>" class="icon">
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
                    Chỉnh sửa ảnh sản phẩm
                </div>
                <div class="card-body col-md-12 mx-auto">
                    <?php if (!empty($tb)): ?>
                        <div class="alert alert-danger text-center">
                            <?php echo $tb; ?>
                        </div>
                    <?php endif; ?>
                    <div class="row">
                        <!-- Hình ảnh sản phẩm -->
                        <div class="col-md-5 d-flex align-items-center justify-content-center">
                            <div class="p-4">
                                <img src="../../Images/SanPham/<?php echo htmlspecialchars($chiTiet['AnhBia']); ?>" 
                                     class="img-fluid" style="max-width: 300px; height: 300px" alt="Ảnh sản phẩm">
                            </div>
                        </div>
                        <!-- Form chỉnh sửa -->
                        <div class="col-md-7 d-flex flex-column justify-content-center">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="MaSP" value="<?php echo htmlspecialchars($chiTiet['MaSP']); ?>">
                                <input type="hidden" name="MaMau" value="<?php echo htmlspecialchars($chiTiet['MaMau']); ?>">
                                <table class="table1 w-100">
                                    <tr>
                                        <td><strong>Sản phẩm:</strong></td>
                                        <td>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($chiTiet['TenSP']); ?>" 
                                                   disabled readonly />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Màu sắc:</strong></td>
                                        <td>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($chiTiet['TenMau']); ?>" 
                                                   disabled readonly />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ảnh bìa:</strong></td>
                                        <td>
                                            <input type="text" name="AnhBia" id="AnhBia" class="form-control" 
                                                   value="<?php echo htmlspecialchars($chiTiet['AnhBia']); ?>" readonly />
                                            <input type="file" name="Avatar" id="Avatar" accept="image/*" class="mt-2" />
                                        </td>
                                    </tr>
                                </table>
                                <div class="form-group mt-3">
                                    <div class="text-center d-flex justify-content-center gap-2">
                                        <a href="product_details.php?id=<?php echo $id; ?>&action=edit" class="btn1">Quay lại</a>
                                        <input type="submit" name="edit" value="Lưu" class="btn2"/>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
    <script>
        $(function () {
            var fileupload = $("#Avatar");
            fileupload.change(function () {
                var fileName = $(this).val().split('\\').pop();
                $("#AnhBia").val(fileName);
            });
        });
    </script>
</body>
</html>

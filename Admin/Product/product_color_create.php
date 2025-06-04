<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Kiểm tra id
$id = isset($_GET['id']) ? $_GET['id'] : null;
if (empty($id)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy thông tin sản phẩm
$sql = "SELECT MaSP, TenSP FROM SanPham WHERE MaSP = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
$stmt->bind_param("s", $id);
if (!$stmt->execute()) {
    die("Lỗi thực thi truy vấn: " . $stmt->error);
}
$result = $stmt->get_result();
$sanPham = $result->fetch_assoc();
$stmt->close();

if (!$sanPham) {
    header("HTTP/1.1 404 Not Found");
    die("Không tìm thấy sản phẩm!");
}

// Lấy danh sách màu sắc
$mauSacs = [];
$result = mysqli_query($conn, "SELECT MaMau, TenMau FROM MauSac");
while ($row = mysqli_fetch_assoc($result)) {
    $mauSacs[$row['MaMau']] = $row['TenMau'];
}

// Xử lý form POST
$tb = '';
if (isset($_POST['create'])) {
    $maSP = $id;
    $maMau = $_POST['MaMau'];
    $anhBia = '';

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
    } else {
        $tb = "Vui lòng chọn ảnh bìa!";
    }

    // Kiểm tra sản phẩm đã có màu này chưa
    $sql = "SELECT COUNT(*) as count FROM ChiTietSanPham WHERE MaSP = ? AND MaMau = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $maSP, $maMau);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();

    if ($count > 0) {
        $tb = "Đã tồn tại sản phẩm với màu này!";
    } elseif (!empty($anhBia)) {
        // Lưu chi tiết sản phẩm
        $sql = "INSERT INTO ChiTietSanPham (MaSP, MaMau, AnhBia) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $tb = "Lỗi chuẩn bị truy vấn: " . $conn->error;
        } else {
            $stmt->bind_param("sss", $maSP, $maMau, $anhBia);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: product_details.php?id=" . $maSP . "&action=detail");
                exit;
            } else {
                $tb = "Lỗi khi thêm chi tiết sản phẩm: " . $stmt->error;
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
    <title>Thêm màu sản phẩm</title>
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
                    Thêm màu sản phẩm
                </div>
                <div class="card-body col-md-8 mx-auto">
                    <?php if (!empty($tb)): ?>
                        <div class="alert alert-danger text-center">
                            <?php echo $tb; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="MaSP" value="<?php echo $sanPham['MaSP']; ?>">
                        <table class="table1">
                            <tr>
                                <td class="col-md-3"><strong>Mã sản phẩm:</strong></td>
                                <td>
                                    <input type="text" value="<?php echo $sanPham['MaSP']; ?>" 
                                           disabled readonly class="form-control" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Tên sản phẩm:</strong></td>
                                <td>
                                    <input type="text" value="<?php echo $sanPham['TenSP']; ?>" 
                                           disabled readonly class="form-control" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Màu sắc:</strong></td>
                                <td>
                                    <select name="MaMau" class="form-control" required>
                                        <option value="">-- Chọn màu sắc --</option>
                                        <?php
                                        foreach ($mauSacs as $key => $value) {
                                            $selected = (isset($_POST['MaMau']) && $_POST['MaMau'] == $key) ? 'selected' : '';
                                            echo "<option value='$key' $selected>$value</option>";
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
                                <a href="product_details.php?id=<?php echo $id; ?>&action=detail" class="btn1">Quay lại</a>
                                <input type="submit" name="create" value="Thêm màu" class="btn2"/>
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

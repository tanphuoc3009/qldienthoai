<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaHSX từ URL
$maHSX = isset($_GET['id']) ? trim($_GET['id']) : '';
if (empty($maHSX)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy thông tin hãng sản xuất
$sql = "SELECT MaHSX, TenHSX FROM HangSanXuat WHERE MaHSX = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
$stmt->bind_param("s", $maHSX);
$stmt->execute();
$result = $stmt->get_result();
$hangSanXuat = $result->fetch_assoc();
$stmt->close();

if (!$hangSanXuat) {
    header("HTTP/1.1 404 Not Found");
    die("Không tìm thấy hãng sản xuất!");
}

// Xử lý form chỉnh sửa
$tb = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    $tenHSX = trim($_POST['TenHSX']);
        // Kiểm tra trùng lặp tên hãng (trừ chính hãng đang chỉnh sửa)
    $sql = "SELECT COUNT(*) as count FROM HangSanXuat WHERE TenHSX = ? AND MaHSX != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $tenHSX, $maHSX);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->fetch_assoc()['count'] > 0;
    $stmt->close();

    // Cập nhật hãng sản xuất
    $sql = "UPDATE HangSanXuat SET TenHSX = ? WHERE MaHSX = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $tenHSX, $maHSX);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: brand.php");
            exit;
        } else {
            $tb = "Lỗi khi cập nhật hãng sản xuất: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $tb = "Lỗi chuẩn bị truy vấn: " . $conn->error;
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
    <title>Chỉnh sửa hãng sản xuất</title>
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
                        <a class="dropdown-item" href="brand.php">
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
                    Chỉnh sửa hãng sản xuất
                </div>
                <div class="card-body col-md-8 mx-auto">
                    <?php if (!empty($tb)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($tb); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="brand_edit.php?id=<?php echo htmlspecialchars($maHSX); ?>">
                        <table class="table1">
                            <tr>
                                <td class="col-md-3"><strong>Mã hãng:</strong></td>
                                <td>
                                    <input type="hidden" name="MaHSX" value="<?php echo htmlspecialchars($hangSanXuat['MaHSX']); ?>" />
                                    <input type="text" value="<?php echo htmlspecialchars($hangSanXuat['MaHSX']); ?>" 
                                           disabled readonly class="form-control" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Tên hãng:</strong></td>
                                <td>
                                    <input type="text" name="TenHSX" class="form-control" required 
                                           value="<?php echo isset($_POST['TenHSX']) ? htmlspecialchars($_POST['TenHSX']) : htmlspecialchars($hangSanXuat['TenHSX']); ?>" />
                                </td>
                            </tr>
                        </table>
                        <div class="form-group mt-3">
                            <div class="text-center d-flex justify-content-center gap-2">
                                <a href="brand.php" class="btn1">Quay lại</a>
                                <input type="submit" name="edit" value="Lưu" class="btn2" />
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

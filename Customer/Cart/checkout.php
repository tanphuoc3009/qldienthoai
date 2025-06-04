<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy thông tin người dùng và giỏ hàng
$gioHang = $_SESSION['GioHang'];
$nguoiDung = $_SESSION['user'];
$diaChiNhanHang = htmlspecialchars($nguoiDung['DiaChi']);

// Hàm tạo mã đơn hàng tự động
function layMaDH($conn) {
    $sql = "SELECT MaDDH FROM DonDatHang ORDER BY MaDDH DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['MaDDH'];
        $num = (int)substr($lastId, 2) + 1;
        return 'DH' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
    return 'DH001';
}

// Xử lý đặt hàng
if (isset($_POST['confirm'])) {
    $diaChi = isset($_POST['diaChi']) ? $_POST['diaChi'] : '';
    $thanhToan = isset($_POST['thanhToan']) ? (int)$_POST['thanhToan'] : null;

    // Tạo mã đơn hàng
    $maDon = layMaDH($conn);

    // Lưu đơn hàng
    $sql = "INSERT INTO DonDatHang (MaDDH, Ngay, TinhTrang, ThanhToan, DiaChiNhanHang, MaKhachHang, MaNhanVien) 
            VALUES (?, NOW(), 0, 0, ?, ?, NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $maDon, $diaChi, $nguoiDung['MaNguoiDung']);
    $stmt->execute();
    $stmt->close();

    // Lưu chi tiết đơn hàng
    foreach ($gioHang as $item) {
        $sql = "INSERT INTO ChiTietDDH (MaDDH, MaSP, MaMau, SoLuong) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $maDon, $item['MaSP'], $item['MaMau'], $item['SoLuong']);
        $stmt->execute();
        $stmt->close();
    }

    // Xử lý theo phương thức thanh toán
    if ($thanhToan == 0) { // COD
        $_SESSION['GioHang'] = [];
        $_SESSION['SLMatHang'] = 0;
        header("Location: ../Order/order.php");
        exit;
    } elseif ($thanhToan == 1) { // MoMo
        header("Location: momo.php?id=" . urlencode($maDon));
        exit;
    } elseif ($thanhToan == 2) { // ZaloPay
        header("Location: zalopay.php?id=" . urlencode($maDon));
        exit;
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đặt hàng</title>
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
                <i class="fas fa-shopping-cart"></i> Giỏ hàng(<span id="SLMatHang"><?php echo $_SESSION['SLMatHang']; ?></span>)
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
                Xác nhận đặt hàng
            </div>
            <form method="post">
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" style="text-align: center"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <!-- Thông tin đơn hàng -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered text-center align-middle">
                            <thead style="font-weight: bold">
                                <tr>
                                    <th>STT</th>
                                    <th>Sản phẩm</th>
                                    <th>Hình ảnh</th>
                                    <th>Màu sắc</th>
                                    <th>Số lượng</th>
                                    <th>Giá bán</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $index = 0; $tongTien = 0; ?>
                                <?php foreach ($gioHang as $item): ?>
                                    <tr>
                                        <td><?php echo ++$index; ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['TenSP']); ?></strong></td>
                                        <td>
                                            <img src="../../Images/SanPham/<?php echo htmlspecialchars($item['AnhBia']); ?>" 
                                                 alt="Ảnh sản phẩm" class="img-thumbnail" style="width: 80px; height: 80px; border: none">
                                        </td>
                                        <td><?php echo htmlspecialchars($item['TenMau']); ?></td>
                                        <td><?php echo $item['SoLuong']; ?></td>
                                        <td class="text-end"><?php echo number_format($item['GiaBan'], 0, ',', '.'); ?> đ</td>
                                        <td class="text-end fw-bold" id="thanhTien-<?php echo $item['MaSP']; ?>-<?php echo $item['MaMau']; ?>">
                                            <?php 
                                            $thanhTien = $item['GiaBan'] * $item['SoLuong'];
                                            $tongTien += $thanhTien;
                                            echo number_format($thanhTien, 0, ',', '.'); 
                                            ?> đ
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="fw-bold text-success">
                                    <td colspan="6" class="text-end" style="color: red; font-weight: bold; font-size: 18px">Tổng Tiền:</td>
                                    <td class="text-end" style="color: red; font-weight: bold; font-size: 18px" id="totalPrice">
                                        <?php echo number_format($tongTien, 0, ',', '.'); ?> đ
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Địa chỉ nhận hàng -->
                    <div class="form-group mb-3" style="width: 25%">
                        <label class="fw-bold mb-1">Địa chỉ nhận hàng</label>
                        <input type="text" class="form-control" id="diaChiNhanHang" name="diaChi" value="<?php echo $diaChiNhanHang; ?>" required>
                    </div>
                    <!-- Phương thức thanh toán -->
                    <div class="form-group mb-3" style="width: 25%">
                        <label class="fw-bold mb-1">Phương thức thanh toán</label>
                        <select name="thanhToan" class="form-control" required>
                            <option value="">--Chọn phương thức thanh toán--</option>
                            <option value="0">Thanh toán khi nhận hàng (COD)</option>
                            <option value="1">Thanh toán qua MoMo</option>
                            <option value="2">Thanh toán qua ZaloPay</option>
                        </select>
                    </div>
                    <div class="text-center d-flex justify-content-center align-items-center mt-3">
                        <div class="d-flex justify-content-center gap-2">
                            <a href="cart.php" class="btn1">Quay lại</a>
                            <button type="submit" name="confirm" class="btn2">Xác nhận đặt hàng</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>
</body>
</html>
<?php
require ('../../config.php');

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaDDH từ GET
$maDDH = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($maDDH)) {
    header("HTTP/1.1 400 Bad Request");
    die("Mã đơn hàng không hợp lệ!");
}

// Xây dựng truy vấn SQL
$sql = "SELECT ddh.MaDDH, ddh.MaKhachHang, ddh.DiaChiNhanHang, ddh.TinhTrang, ddh.ThanhToan,
               ct.MaSP, ct.MaMau, ct.SoLuong,
               sp.TenSP, sp.GiaBan,
               ms.TenMau,
               (ct.SoLuong * sp.GiaBan) AS ThanhTien,
               (SELECT AnhBia FROM ChiTietSanPham ctsp WHERE ctsp.MaSP = ct.MaSP AND ctsp.MaMau = ct.MaMau LIMIT 1) AS AnhBia
        FROM DonDatHang ddh
        JOIN ChiTietDDH ct ON ddh.MaDDH = ct.MaDDH
        JOIN SanPham sp ON ct.MaSP = sp.MaSP
        JOIN MauSac ms ON ct.MaMau = ms.MaMau
        WHERE ddh.MaDDH = ? AND ddh.MaKhachHang = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
$stmt->bind_param("ss", $maDDH, $_SESSION['user']['MaNguoiDung']);
$stmt->execute();
$result = $stmt->get_result();
$chiTiet = [];
while ($row = $result->fetch_assoc()) {
    $chiTiet[] = $row;
}
$stmt->close();

// Kiểm tra đơn hàng tồn tại và quyền sở hữu
if (empty($chiTiet)) {
    header("HTTP/1.1 404 Not Found");
    die("Đơn hàng không tồn tại hoặc bạn không có quyền xem!");
}

// Tính tổng tiền
$tongTien = array_sum(array_column($chiTiet, 'ThanhTien'));

// Xử lý thông tin người dùng và số lượng mặt hàng
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . $_SESSION['user']['HoTen'] : "";
$slMatHang = isset($_SESSION['SLMatHang']) ? $_SESSION['SLMatHang'] : 0;

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng</title>
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
            <div class="user-greeting"><?php echo $nguoiDung; ?></div>
            <a href="../Cart/cart.php" class="icon">
                <i class="fas fa-shopping-cart"></i> Giỏ hàng(<span id="SLMatHang"><?php echo $slMatHang; ?></span>)
            </a>
            <a href="order.php" class="icon">
                <i class="fas fa-receipt"></i> Đơn hàng
            </a>
            <a href="../Account/profile.php?id=<?php echo $_SESSION['user']['MaNguoiDung']; ?>" class="icon">
                <i class="fas fa-user"></i> Hồ sơ
            </a>
            <a href="../../logout.php" class="icon">
                <i class="fas fa-sign-out"></i> Đăng xuất
            </a>
        </div>
    </div>

    <!-- Nội dung chi tiết đơn hàng -->
    <div class="container">
        <div class="card shadow-lg pb-4 mt-4" style="max-width: 100%; margin: 0 auto;">
            <div class="card-header brand-header text-center">
                Chi tiết đơn hàng <?php echo htmlspecialchars($maDDH); ?>
            </div>
            <div class="card-body">
                <!-- Bảng chi tiết sản phẩm -->
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-center align-middle">
                        <thead style="font-weight: bold;">
                            <tr>
                                <th>STT</th>
                                <th>Sản Phẩm</th>
                                <th>Hình Ảnh</th>
                                <th>Màu Sắc</th>
                                <th>Số Lượng</th>
                                <th>Giá Bán</th>
                                <th>Thành Tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $index = 0; ?>
                            <?php foreach ($chiTiet as $item): ?>
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
                                    <td class="text-end fw-bold"><?php echo number_format($item['ThanhTien'], 0, ',', '.'); ?> đ</td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="fw-bold text-success">
                                <td colspan="6" class="text-end" style="color: red; font-weight: bold; font-size: 18px">Tổng Tiền:</td>
                                <td class="text-end" style="color: red; font-weight: bold; font-size: 18px"><?php echo number_format($tongTien, 0, ',', '.'); ?> đ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Thông tin đơn hàng -->
                <div class="mb-3 mt-1">
                    <strong>Địa chỉ nhận hàng:</strong> <?php echo htmlspecialchars($chiTiet[0]['DiaChiNhanHang']); ?>
                </div>
                <div class="mb-3 mt-1">
                    <strong>Tình trạng đơn hàng:</strong>
                    <?php
                    $tinhTrang = $chiTiet[0]['TinhTrang'];
                    $tinhTrangText = '';
                    $tinhTrangClass = '';
                    switch ($tinhTrang) {
                        case 0:
                            $tinhTrangText = 'Chờ xác nhận';
                            $tinhTrangClass = 'bg-secondary';
                            break;
                        case 1:
                            $tinhTrangText = 'Chờ giao hàng';
                            $tinhTrangClass = 'bg-warning';
                            break;
                        case 2:
                            $tinhTrangText = 'Thành công';
                            $tinhTrangClass = 'bg-success';
                            break;
                        case 3:
                            $tinhTrangText = 'Đã hủy';
                            $tinhTrangClass = 'bg-danger';
                            break;
                    }
                    ?>
                    <span class="badge <?php echo $tinhTrangClass; ?>" style="font-size: 16px"><?php echo $tinhTrangText; ?></span>
                </div>
                <div class="mt-1">
                    <strong>Tình trạng thanh toán:</strong>
                    <?php
                    $thanhToan = $chiTiet[0]['ThanhToan'];
                    $thanhToanText = $thanhToan == 0 ? 'Chưa thanh toán' : 'Đã thanh toán';
                    $thanhToanColor = $thanhToan == 0 ? 'red' : 'green';
                    ?>
                    <span style="font-size: 16px; color: <?php echo $thanhToanColor; ?>"><?php echo $thanhToanText; ?></span>
                </div>
            </div>
            <div class="text-center d-flex justify-content-center gap-2">
                <a href="order.php" class="btn2">Quay lại</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>
</body>
</html>

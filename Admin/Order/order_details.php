<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['AD', 'NV'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaDDH từ GET
$maDDH = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($maDDH)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Xây dựng truy vấn SQL
$sql = "SELECT ddh.MaDDH, ddh.DiaChiNhanHang, ddh.TinhTrang, ddh.ThanhToan, ddh.MaKhachHang, ddh.MaNhanVien,
               ct.MaSP, ct.MaMau, ct.SoLuong,
               sp.TenSP, sp.GiaBan,
               ms.TenMau,
               (ct.SoLuong * sp.GiaBan) AS ThanhTien,
               kh.HoTen AS TenKH, kh.SDT,
               (SELECT AnhBia FROM ChiTietSanPham ctsp WHERE ctsp.MaSP = ct.MaSP AND ctsp.MaMau = ct.MaMau) AS AnhBia
        FROM DonDatHang ddh
        JOIN ChiTietDDH ct ON ddh.MaDDH = ct.MaDDH
        JOIN SanPham sp ON ct.MaSP = sp.MaSP
        JOIN MauSac ms ON ct.MaMau = ms.MaMau
        JOIN NguoiDung kh ON ddh.MaKhachHang = kh.MaNguoiDung
        WHERE ddh.MaDDH = ?";
$params = [$maDDH];
$types = 's';

// Kiểm tra quyền nhân viên
if ($_SESSION['user']['VaiTro'] === 'NV') {
    $sql .= " AND ddh.MaNhanVien = ?";
    $params[] = $_SESSION['user']['MaNguoiDung'];
    $types .= 's';
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
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
    die("Yêu cầu không hợp lệ!");
}

// Tính tổng tiền
$tongTien = array_sum(array_column($chiTiet, 'ThanhTien'));

// Xử lý thông tin người dùng
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . htmlspecialchars($_SESSION['user']['HoTen']) : '';

// Lấy danh sách nhân viên dùng cho dropdown-list
$sql = "SELECT MaNguoiDung, HoTen FROM NguoiDung WHERE VaiTro = 'NV'";
$result = $conn->query($sql);
$nhanViens = [];
while ($row = $result->fetch_assoc()) {
    $nhanViens[] = $row;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng</title>
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
                        <a class="dropdown-item" href="../Account/account.php">
                            <div class="icon" style="color: black">Quản lý người dùng</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="order.php">
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
                    <strong>Tên khách hàng:</strong> <?php echo htmlspecialchars($chiTiet[0]['TenKH']); ?>
                </div>
                <div class="mb-3 mt-1">
                    <strong>Số điện thoại:</strong> <?php echo htmlspecialchars($chiTiet[0]['SDT']); ?>
                </div>
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
            <!-- Thao tác -->
            <div class="text-center d-flex justify-content-center gap-2">
                <?php if ($tinhTrang == 0): ?>
                    <a href="order.php" class="btn2">Quay lại</a>
                    <button type="button" class="btn btn-danger cancel-order-btn" 
                            data-maddh="<?php echo htmlspecialchars($maDDH); ?>" 
                            style="border-radius: 5px; color: white;">
                        Hủy đơn
                    </button>
                    <button type="button" class="btn btn-success accept-order-btn" 
                            data-maddh="<?php echo htmlspecialchars($maDDH); ?>" 
                            style="border-radius: 5px; color: white;">
                        Duyệt đơn
                    </button>
                <?php elseif ($tinhTrang == 1): ?>
                    <?php if ($_SESSION['user']['VaiTro'] === 'NV'): ?>
                        <a href="order.php" class="btn2">Quay lại</a>
                        <button type="button" class="btn btn-success complete-order-btn" 
                                data-maddh="<?php echo htmlspecialchars($maDDH); ?>" 
                                style="border-radius: 5px; color: white;">
                            Hoàn thành
                        </button>
                    <?php else: ?>
                        <a href="order.php" class="btn2">Quay lại</a>
                        <button type="button" class="btn btn-danger cancel-order-btn" 
                            data-maddh="<?php echo htmlspecialchars($maDDH); ?>" 
                            style="border-radius: 5px; color: white;">
                        Hủy đơn
                    </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="order.php" class="btn2">Quay lại</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Modal xác nhận hủy đơn -->
    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-center" style="background: red; color: white;">
                    <div class="modal-title" id="cancelModalLabel" 
                    style="font-weight: bold; font-size: 20px; align-content: center;">
                        Xác nhận hủy đơn hàng
                    </div>
                </div>
                <div class="modal-body">
                    <p class="text-center" style="color: red; font-weight: bold;">
                        Bạn có chắc chắn muốn hủy đơn hàng <strong><?php echo $maDDH ?></strong> không?
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn2" data-dismiss="modal">Quay lại</button>
                        <form id="cancelForm" method="post" style="display: inline;">
                            <button type="submit" name="confirm_cancel" class="btn btn-danger">Hủy đơn</button>
                        </form>
                    </div>                    
                </div>
            </div>
        </div>
    </div>
    <!-- Modal xác nhận duyệt đơn -->
    <div class="modal fade" id="acceptModal" tabindex="-1" role="dialog" aria-labelledby="acceptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-center" style="background: green; color: white;">
                    <div class="modal-title" id="acceptModalLabel" 
                         style="font-weight: bold; font-size: 20px; align-content: center;">
                        Phân công nhân viên
                    </div>
                </div>
                <div class="modal-body">
                    <p class="text-center" style="color: green; font-weight: bold;">
                        Bạn có chắc chắn muốn duyệt đơn hàng <strong><?php echo $maDDH ?></strong> không?
                    </p>
                    <form id="acceptForm" method="post" style="display: inline">
                        <div class="form-group">
                            <div class="mt-2 d-flex align-items-center">
                                <label for="maNV" class = "col-md-5"><strong>Phân công nhân viên:</strong></label>
                                <select name="maNV" id="maNV" class="form-control text-center" style="width: 100%;" required>
                                    <option value="">--Chọn nhân viên--</option>
                                    <?php foreach ($nhanViens as $nv): ?>
                                        <option value="<?php echo htmlspecialchars($nv['MaNguoiDung']); ?>">
                                            <?php echo $nv['MaNguoiDung'] . " - " . $nv['HoTen']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center gap-2 mt-3">
                            <button type="button" class="btn2" data-dismiss="modal">Quay lại</button>
                            <button type="submit" name="confirm_accept" class="btn btn-success">Xác nhận</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal xác nhận hoàn thành đơn -->
    <div class="modal fade" id="completeModal" tabindex="-1" role="dialog" aria-labelledby="completeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-center" style="background: green; color: white;">
                    <div class="modal-title" id="completeModalLabel" 
                         style="font-weight: bold; font-size: 20px; align-content: center;">
                        Xác nhận hoàn thành đơn hàng
                    </div>
                </div>
                <div class="modal-body">
                    <p class="text-center" style="color: green; font-weight: bold;">
                        Bạn có chắc chắn muốn hoàn thành đơn hàng <strong><?php echo $maDDH ?></strong> không?
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn2" data-dismiss="modal">Quay lại</button>
                        <form id="completeForm" method="post" action="">
                            <button type="submit" name="confirm_complete" class="btn btn-success">Hoàn thành</button>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function() {
            // Xử lý nút Hủy đơn
            $('.cancel-order-btn').click(function() {
                var maDDH = $(this).data('maddh');
                $('#cancelForm').attr('action', 'order_cancel.php?id=' + encodeURIComponent(maDDH));
                $('#cancelModal').modal('show');
            });

            // Xử lý nút Duyệt đơn
            $('.accept-order-btn').click(function() {
                var maDDH = $(this).data('maddh');
                $('#acceptForm').attr('action', 'order_accept.php?id=' + encodeURIComponent(maDDH));
                $('#acceptModal').modal('show');
            });

            // Xử lý nút Hoàn thành
            $('.complete-order-btn').click(function() {
                var maDDH = $(this).data('maddh');
                $('#completeForm').attr('action', 'order_complete.php?id=' + encodeURIComponent(maDDH));
                $('#completeModal').modal('show');
            });
        });
    </script>
</body>
</html>
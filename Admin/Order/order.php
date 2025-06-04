<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['AD', 'NV'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy tham số tìm kiếm từ GET
$maDDH = isset($_GET['MaDDH']) ? $_GET['MaDDH'] : '';
$tuNgay = isset($_GET['TuNgay']) ? $_GET['TuNgay'] : '';
$denNgay = isset($_GET['DenNgay']) ? $_GET['DenNgay'] : '';
$tinhTrang = isset($_GET['TinhTrang']) && $_GET['TinhTrang'] !== '' ? (int)$_GET['TinhTrang'] : null;
$thanhToan = isset($_GET['ThanhToan']) && $_GET['ThanhToan'] !== '' ? (int)$_GET['ThanhToan'] : null;
$diaChi = isset($_GET['DiaChi']) ? $_GET['DiaChi'] : '';
$tongTienMin = isset($_GET['TongTienMin']) && is_numeric($_GET['TongTienMin']) ? (int)$_GET['TongTienMin'] : 0;
$tongTienMax = isset($_GET['TongTienMax']) && is_numeric($_GET['TongTienMax']) ? (int)$_GET['TongTienMax'] : 500000000;
$maKH = isset($_GET['MaKH']) ? $_GET['MaKH'] : '';
$maNV = isset($_GET['MaNV']) ? $_GET['MaNV'] : '';

// Chuẩn bị dữ liệu cho giao diện
$viewData = [
    'MaDDH' => $maDDH,
    'TuNgay' => $tuNgay,
    'DenNgay' => $denNgay,
    'TinhTrang' => $tinhTrang,
    'ThanhToan' => $thanhToan,
    'DiaChi' => $diaChi,
    'TongTienMin' => $tongTienMin,
    'TongTienMax' => $tongTienMax,
    'MaKH' => $maKH,
    'MaNV' => $maNV
];

// Xây dựng truy vấn SQL
$sql = "SELECT ddh.MaDDH, ddh.Ngay, ddh.TinhTrang, ddh.ThanhToan, ddh.DiaChiNhanHang, 
               ddh.MaKhachHang, ddh.MaNhanVien, kh.HoTen as TenKhachHang, nv.HoTen as TenNhanVien,
               SUM(ct.SoLuong * sp.GiaBan) as TongTien
        FROM DonDatHang ddh
        JOIN ChiTietDDH ct ON ddh.MaDDH = ct.MaDDH
        JOIN SanPham sp ON ct.MaSP = sp.MaSP
        LEFT JOIN NguoiDung kh ON ddh.MaKhachHang = kh.MaNguoiDung
        LEFT JOIN NguoiDung nv ON ddh.MaNhanVien = nv.MaNguoiDung
        WHERE 1=1";
$params = [];
$types = '';

if ($_SESSION['user']['VaiTro'] === 'NV') {
    $sql .= " AND ddh.MaNhanVien = ?";
    $params[] = $_SESSION['user']['MaNguoiDung'];
    $types .= 's';
}

if (!empty($maDDH)) {
    $sql .= " AND ddh.MaDDH LIKE ?";
    $params[] = "%$maDDH%";
    $types .= 's';
}
if (!empty($tuNgay)) {
    $sql .= " AND ddh.Ngay >= ?";
    $params[] = $tuNgay;
    $types .= 's';
}
if (!empty($denNgay)) {
    $sql .= " AND ddh.Ngay <= ?";
    $params[] = $denNgay;
    $types .= 's';
}
if ($tinhTrang !== null) {
    $sql .= " AND ddh.TinhTrang = ?";
    $params[] = $tinhTrang;
    $types .= 'i';
}
if ($thanhToan !== null) {
    $sql .= " AND ddh.ThanhToan = ?";
    $params[] = $thanhToan;
    $types .= 'i';
}
if (!empty($diaChi)) {
    $sql .= " AND ddh.DiaChiNhanHang LIKE ?";
    $params[] = "%$diaChi%";
    $types .= 's';
}
if (!empty($maKH)) {
    $sql .= " AND ddh.MaKhachHang LIKE ?";
    $params[] = "%$maKH%";
    $types .= 's';
}
if (!empty($maNV)) {
    $sql .= " AND ddh.MaNhanVien LIKE ?";
    $params[] = "%$maNV%";
    $types .= 's';
}
$sql .= " GROUP BY ddh.MaDDH, ddh.Ngay, ddh.TinhTrang, ddh.ThanhToan, ddh.DiaChiNhanHang, 
                    ddh.MaKhachHang, ddh.MaNhanVien, kh.HoTen, nv.HoTen";
$sql .= " HAVING SUM(ct.SoLuong * sp.GiaBan) BETWEEN ? AND ?";
$params[] = $tongTienMin;
$params[] = $tongTienMax;
$types .= 'ii';
$sql .= " ORDER BY ddh.MaDDH DESC";

// Phân trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = 10;
$offset = ($page - 1) * $rowsPerPage;

// Đếm tổng số đơn hàng
$countStmt = $conn->prepare($sql);
if (!$countStmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->num_rows;
$countStmt->close();

// Đếm tổng số trang
$maxPage = ceil($totalRows / $rowsPerPage);

// Lấy dữ liệu đơn hàng theo phân trang
$sql .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $rowsPerPage;
$types .= 'ii';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$donHangs = [];
while ($row = $result->fetch_assoc()) {
    $donHangs[$row['MaDDH']] = $row;
}
$stmt->close();

// Xử lý thông tin người dùng
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . htmlspecialchars($_SESSION['user']['HoTen']) : '';

mysqli_close($conn);

// Hàm giữ lại tham số tìm kiếm
function buildQueryString($page) {
    $params = $_GET;
    $params['page'] = $page;
    return http_build_query($params);
}
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

    <!-- Phần tìm kiếm đơn hàng -->
    <div class="container mt-4 mx-auto" style="width: 830px">
        <div class="card shadow-lg pb-4">
            <div class="card-header brand-header text-center">
                Tìm kiếm đơn hàng
            </div>
            <div class="card-body">
                <form id="search-form" method="GET" action="order.php">
                    <table class="table1" align="center" cellpadding="8" style="width: 100%;">
                        <tr>
                            <td><strong>Mã đơn hàng:</strong></td>
                            <td>
                                <input type="text" name="MaDDH" class="form-control" style="width: 100%;" 
                                       value="<?php echo htmlspecialchars($viewData['MaDDH']); ?>">
                            </td>
                            <td><strong>Mã khách hàng:</strong></td>
                            <td>
                                <input type="text" name="MaKH" class="form-control" style="width: 100%;" 
                                       value="<?php echo htmlspecialchars($viewData['MaKH']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Từ ngày:</strong></td>
                            <td>
                                <input type="date" name="TuNgay" class="form-control" style="width: 100%;" 
                                       value="<?php echo $viewData['TuNgay']; ?>">
                            </td>
                            <td><strong>Đến ngày:</strong></td>
                            <td>
                                <input type="date" name="DenNgay" class="form-control" style="width: 100%;" 
                                       value="<?php echo $viewData['DenNgay']; ?>">
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Tình trạng:</strong></td>
                            <td colspan="3">
                                <div class="row">
                                    <div class="col-3">
                                        <input type="radio" name="TinhTrang" value="0" 
                                               <?php echo $viewData['TinhTrang'] === 0 ? 'checked' : ''; ?>> Chờ xác nhận
                                    </div>
                                    <div class="col-3">
                                        <input type="radio" name="TinhTrang" value="1" 
                                               <?php echo $viewData['TinhTrang'] === 1 ? 'checked' : ''; ?>> Chờ giao hàng
                                    </div>
                                    <div class="col-3">
                                        <input type="radio" name="TinhTrang" value="2" 
                                               <?php echo $viewData['TinhTrang'] === 2 ? 'checked' : ''; ?>> Thành công
                                    </div>
                                    <div class="col-3">
                                        <input type="radio" name="TinhTrang" value="3" 
                                               <?php echo $viewData['TinhTrang'] === 3 ? 'checked' : ''; ?>> Đã hủy
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Thanh toán:</strong></td>
                            <td colspan="3">
                                <div class="row">
                                    <div class="col-3">
                                        <input type="radio" name="ThanhToan" value="0" 
                                               <?php echo $viewData['ThanhToan'] === 0 ? 'checked' : ''; ?>> Chưa thanh toán
                                    </div>
                                    <div class="col-3">
                                        <input type="radio" name="ThanhToan" value="1" 
                                               <?php echo $viewData['ThanhToan'] === 1 ? 'checked' : ''; ?>> Đã thanh toán
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Tổng tiền từ:</strong></td>
                            <td>
                                <input type="range" id="tongTienMin" name="TongTienMin" class="form-range" 
                                       min="0" max="500000000" step="1000000" value="<?php echo $viewData['TongTienMin']; ?>" 
                                       oninput="updateFormattedValues()">
                                <output id="minOutput"><?php echo number_format($viewData['TongTienMin'], 0, ',', '.'); ?></output>đ
                            </td>
                            <td><strong>Đến:</strong></td>
                            <td>
                                <input type="range" id="tongTienMax" name="TongTienMax" class="form-range" 
                                       min="0" max="500000000" step="1000000" value="<?php echo $viewData['TongTienMax']; ?>" 
                                       oninput="updateFormattedValues()">
                                <output id="maxOutput"><?php echo number_format($viewData['TongTienMax'], 0, ',', '.'); ?></output>đ
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Mã nhân viên:</strong></td>
                            <td>
                                <input type="text" name="MaNV" class="form-control" style="width: 100%;" 
                                       value="<?php echo htmlspecialchars($viewData['MaNV']); ?>">
                            </td>
                            <td><strong>Địa chỉ:</strong></td>
                            <td>
                                <input type="text" name="DiaChi" class="form-control" style="width: 100%;" 
                                       value="<?php echo htmlspecialchars($viewData['DiaChi']); ?>">
                            </td>
                        </tr>
                    </table>
                    <div class="text-center d-flex justify-content-center gap-2 mt-3">
                        <button type="reset" class="btn1" id="reset-button">Nhập lại</button>
                        <button type="submit" class="btn2">Tìm kiếm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Phần danh sách đơn hàng -->
    <div class="container">
        <div class="card shadow-lg pb-4 mt-4" style="max-width: 100%; margin: 0 auto;" id="order-list">
            <div class="card-header brand-header text-center">
                Danh sách đơn hàng
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-center align-middle">
                        <thead style="font-weight: bold;">
                            <tr>
                                <th>STT</th>
                                <th>Mã đơn</th>
                                <th>Ngày đặt</th>
                                <th>Tình trạng</th>
                                <th>Địa chỉ</th>
                                <th>Tổng tiền</th>
                                <th>Khách hàng</th>
                                <th>Nhân viên</th>
                                <th>Thanh toán</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = ($page - 1) * $rowsPerPage;
                            foreach ($donHangs as $maDDH => $donHang):
                                $i++;
                                $tinhTrangText = '';
                                $tinhTrangClass = '';
                                switch ($donHang['TinhTrang']) {
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
                                $thanhToanText = $donHang['ThanhToan'] == 0 ? 'Chưa thanh toán' : 'Đã thanh toán';
                                $thanhToanColor = $donHang['ThanhToan'] == 0 ? 'red' : 'green';
                                $diaChi = $donHang['DiaChiNhanHang'];
                                $diaChiRutGon = mb_strlen($diaChi, 'UTF-8') > 30 ? mb_substr($diaChi, 0, 30, 'UTF-8') . '...' : $diaChi;
                            ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><strong><?php echo htmlspecialchars($donHang['MaDDH']); ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($donHang['Ngay'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $tinhTrangClass; ?>" style="font-size: 16px;">
                                        <?php echo $tinhTrangText; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($diaChiRutGon); ?></td>
                                <td class="text-end fw-bold" style="color: red;">
                                    <?php echo number_format($donHang['TongTien'], 0, ',', '.'); ?> đ
                                </td>
                                <td><?php echo htmlspecialchars($donHang['MaKhachHang']); ?></td>
                                <td><?php echo htmlspecialchars($donHang['MaNhanVien']); ?></td>
                                <td>
                                    <span style="font-size: 16px; color: <?php echo $thanhToanColor; ?>">
                                        <?php echo $thanhToanText; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_details.php?id=<?php echo htmlspecialchars($donHang['MaDDH']); ?>" class="btn2">
                                        Chi tiết
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Phân trang -->
            <div>
                <nav class="text-center">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $_SERVER['PHP_SELF'] . '?' . buildQueryString($page - 1); ?>"><</a>
                        </li>
                        <li class="page-item active">
                            <span class="page-link">
                                <?php echo $page; ?> / <?php echo $maxPage ?: 1; ?>
                            </span>
                        </li>
                        <li class="page-item <?php echo $page >= $maxPage ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $_SERVER['PHP_SELF'] . '?' . buildQueryString($page + 1); ?>">></a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="text-end" style="margin-right: 26px">
                <a href="../admin.php">Về trang chủ</a>
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
        // Tự cuộn đến danh sách khi tìm kiếm
        document.addEventListener("DOMContentLoaded", function () {
            if (window.location.search.length > 0) {
                setTimeout(() => {
                    document.getElementById("order-list").scrollIntoView({ behavior: "smooth" });
                }, 0);
            }
        });

        // Định dạng số
        function formatNumber(n) {
            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Reset form
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("reset-button").addEventListener("click", function (event) {
                event.preventDefault();
                let form = document.getElementById("search-form");
                form.querySelectorAll("input").forEach(input => {
                    if (input.type === "text" || input.type === "date") {
                        input.value = "";
                    }
                    if (input.type === "radio") {
                        input.checked = false;
                    }
                    if (input.id === "tongTienMin") {
                        input.value = "0";
                        document.getElementById("minOutput").textContent = formatNumber(0);
                    }
                    if (input.id === "tongTienMax") {
                        input.value = "500000000";
                        document.getElementById("maxOutput").textContent = formatNumber(500000000);
                    }
                });
                window.history.replaceState(null, null, window.location.pathname);
            });
        });

        // Cập nhật giá trị thanh kéo
        function updateFormattedValues() {
            const min = document.getElementById("tongTienMin").value;
            const max = document.getElementById("tongTienMax").value;
            document.getElementById("minOutput").textContent = formatNumber(min);
            document.getElementById("maxOutput").textContent = formatNumber(max);
        }
    </script>
</body>
</html>
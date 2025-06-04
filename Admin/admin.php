<?php
require '../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['AD', 'NV'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

$nguoiDung = $_SESSION['user'];

// Hàm thống kê số lượng và giá trị đơn hàng theo trạng thái
function thongKeDonHang($conn, $nguoiDung) {
    $result = [];
    
    // Truy vấn số lượng đơn hàng theo trạng thái
    $sql = "SELECT TinhTrang, COUNT(*) as SoLuong 
            FROM DonDatHang 
            WHERE 1=1";
    if ($nguoiDung['VaiTro'] === 'NV') {
        $sql .= " AND MaNhanVien = ?";
    }
    $sql .= " GROUP BY TinhTrang";
    
    $stmt = $conn->prepare($sql);
    if ($nguoiDung['VaiTro'] === 'NV') {
        $stmt->bind_param("s", $nguoiDung['MaNguoiDung']);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $result['SLDonChoXacNhan'] = 0;
    $result['SLDonDangGiao'] = 0;
    $result['SLDonThanhCong'] = 0;
    $result['SLDonDaHuy'] = 0;

    foreach ($rows as $row) {
        if ($row['TinhTrang'] == 0) $result['SLDonChoXacNhan'] = $row['SoLuong'];
        elseif ($row['TinhTrang'] == 1) $result['SLDonDangGiao'] = $row['SoLuong'];
        elseif ($row['TinhTrang'] == 2) $result['SLDonThanhCong'] = $row['SoLuong'];
        elseif ($row['TinhTrang'] == 3) $result['SLDonDaHuy'] = $row['SoLuong'];
    }

    // Truy vấn giá trị đơn hàng theo trạng thái
    $sql = "SELECT ddh.TinhTrang, SUM(ct.SoLuong * sp.GiaBan) as GiaTri 
            FROM DonDatHang ddh 
            JOIN ChiTietDDH ct ON ddh.MaDDH = ct.MaDDH 
            JOIN SanPham sp ON ct.MaSP = sp.MaSP 
            WHERE 1=1";
    if ($nguoiDung['VaiTro'] === 'NV') {
        $sql .= " AND ddh.MaNhanVien = ?";
    }
    $sql .= " GROUP BY ddh.TinhTrang";
    
    $stmt = $conn->prepare($sql);
    if ($nguoiDung['VaiTro'] === 'NV') {
        $stmt->bind_param("s", $nguoiDung['MaNguoiDung']);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $result['GTDonChoXacNhan'] = 0;
    $result['GTDonDangGiao'] = 0;
    $result['GTDonThanhCong'] = 0;
    $result['GTDonDaHuy'] = 0;

    foreach ($rows as $row) {
        if ($row['TinhTrang'] == 0) $result['GTDonChoXacNhan'] = $row['GiaTri'];
        elseif ($row['TinhTrang'] == 1) $result['GTDonDangGiao'] = $row['GiaTri'];
        elseif ($row['TinhTrang'] == 2) $result['GTDonThanhCong'] = $row['GiaTri'];
        elseif ($row['TinhTrang'] == 3) $result['GTDonDaHuy'] = $row['GiaTri'];
    }

    return $result;
}

// Hàm thống kê top 5 sản phẩm bán chạy
function sanPhamBanChay($conn) {
    $sql = "SELECT sp.TenSP, SUM(ct.SoLuong) as SoLuong 
            FROM ChiTietDDH ct 
            JOIN DonDatHang ddh ON ct.MaDDH = ddh.MaDDH 
            JOIN SanPham sp ON ct.MaSP = sp.MaSP 
            WHERE ddh.TinhTrang < 3 
            GROUP BY ct.MaSP, sp.TenSP 
            ORDER BY SoLuong DESC 
            LIMIT 5";
    
    $result = mysqli_query($conn, $sql);
    $topSanPham = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $topSanPham[] = $row;
    }

    return [
        'labels' => array_column($topSanPham, 'TenSP'),
        'data' => array_column($topSanPham, 'SoLuong')
    ];
}

// Hàm thống kê doanh thu 6 tháng gần nhất
function doanhThuTheoKy($conn) {
    // Tạo danh sách 6 tháng gần nhất
    $last6Months = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));
        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));
        $last6Months[] = ['Thang' => $month, 'Nam' => $year];
    }

    // Truy vấn doanh thu
    $sql = "SELECT YEAR(ddh.Ngay) as Nam, MONTH(ddh.Ngay) as Thang, 
                   SUM(ct.SoLuong * sp.GiaBan) as DoanhThu 
            FROM DonDatHang ddh 
            JOIN ChiTietDDH ct ON ddh.MaDDH = ct.MaDDH 
            JOIN SanPham sp ON ct.MaSP = sp.MaSP 
            WHERE ddh.TinhTrang = 2 
            GROUP BY YEAR(ddh.Ngay), MONTH(ddh.Ngay)";
    
    $result = mysqli_query($conn, $sql);
    $doanhThu = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $doanhThu[] = $row;
    }

    // Ghép dữ liệu
    $doanhThuTheoThang = [];
    foreach ($last6Months as $month) {
        $dt = 0;
        foreach ($doanhThu as $item) {
            if ($item['Thang'] == $month['Thang'] && $item['Nam'] == $month['Nam']) {
                $dt = $item['DoanhThu'];
                break;
            }
        }

        $doanhThuTheoThang[] = [
            'Key' => $month['Thang'] . '/' . $month['Nam'],
            'DoanhThu' => $dt
        ];
    }

    return [
        'labels' => array_column($doanhThuTheoThang, 'Key'),
        'data' => array_column($doanhThuTheoThang, 'DoanhThu'),
    ];
}

// Thống kê đơn hàng
$thongKe = thongKeDonHang($conn, $nguoiDung);

// Thống kê sản phẩm bán chạy
$topSanPham = sanPhamBanChay($conn);
$spLabels = json_encode($topSanPham['labels'], JSON_UNESCAPED_UNICODE);
$spData = json_encode($topSanPham['data']);

// Thống kê doanh thu theo kỳ
$doanhThuKy = doanhThuTheoKy($conn);
$thangLabels = json_encode($doanhThuKy['labels']);
$doanhThuData = json_encode($doanhThuKy['data']);

// Thống kê doanh thu theo sản phẩm và hãng sản xuất
$tuNgay = isset($_GET['tuNgay']) && !empty($_GET['tuNgay']) ? $_GET['tuNgay'] : null;
$denNgay = isset($_GET['denNgay']) && !empty($_GET['denNgay']) ? $_GET['denNgay'] : null;
$selectedHSX = isset($_GET['HSX']) ? (array)$_GET['HSX'] : [];

$sqlHSX = "SELECT MaHSX, TenHSX FROM HangSanXuat";
$hsxList = mysqli_query($conn, $sqlHSX);
$hsxOptions = [];
while ($row = mysqli_fetch_assoc($hsxList)) {
    $hsxOptions[] = $row;
}

$sql = "SELECT hsx.TenHSX, sp.MaSP, sp.TenSP, sp.GiaBan, 
               SUM(ct.SoLuong) as TongSoLuong, 
               SUM(ct.SoLuong * sp.GiaBan) as TongTien 
        FROM ChiTietDDH ct 
        JOIN DonDatHang ddh ON ct.MaDDH = ddh.MaDDH 
        JOIN SanPham sp ON ct.MaSP = sp.MaSP 
        JOIN HangSanXuat hsx ON sp.MaHSX = hsx.MaHSX 
        WHERE ddh.TinhTrang = 2";
$params = [];
$types = '';

if ($tuNgay) {
    $sql .= " AND ddh.Ngay >= ?";
    $params[] = $tuNgay;
    $types .= 's';
}
if ($denNgay) {
    $sql .= " AND ddh.Ngay <= ?";
    $params[] = $denNgay;
    $types .= 's';
}
if (!empty($selectedHSX)) {
    $placeholders = implode(',', array_fill(0, count($selectedHSX), '?'));
    $sql .= " AND sp.MaHSX IN ($placeholders)";
    $params = array_merge($params, $selectedHSX);
    $types .= str_repeat('s', count($selectedHSX));
}

$sql .= " GROUP BY sp.MaSP, sp.TenSP, sp.GiaBan, hsx.TenHSX 
          ORDER BY hsx.TenHSX, sp.MaSP";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();

// Nhóm dữ liệu theo hãng sản xuất
$groupedData = [];
foreach ($data as $row) {
    $tenHSX = $row['TenHSX'];
    if (!isset($groupedData[$tenHSX])) {
        $groupedData[$tenHSX] = [
            'TenHSX' => $tenHSX,
            'TongTien' => 0,
            'SanPhams' => []
        ];
    }
    $groupedData[$tenHSX]['SanPhams'][] = [
        'MaSP' => $row['MaSP'],
        'TenSP' => $row['TenSP'],
        'GiaBan' => $row['GiaBan'],
        'TongSoLuong' => $row['TongSoLuong'],
        'TongTien' => $row['TongTien']
    ];
    $groupedData[$tenHSX]['TongTien'] += $row['TongTien'];
}
$groupedData = array_values($groupedData);

$tongDoanhThu = array_sum(array_column($groupedData, 'TongTien'));

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê</title>
    <link rel="stylesheet" href="../Content/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../Content/Style.css">
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
                        <a class="dropdown-item" href="admin.php">
                            <div class="icon" style="color: black">Báo cáo - Thống kê</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="Product/product.php">
                            <div class="icon" style="color: black">Quản lý sản phẩm</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="Brand/brand.php">
                            <div class="icon" style="color: black">Quản lý hãng sản xuất</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="Color/color.php">
                            <div class="icon" style="color: black">Quản lý màu sắc</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="Account/account.php">
                            <div class="icon" style="color: black">Quản lý người dùng</div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="Order/order.php">
                            <div class="icon" style="color: black">Quản lý đơn hàng</div>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
        <a href="admin.php">
            <div class="logo" style="margin-left: 30px">
                <img src="../Images/Banner/logo.jpg" alt="Logo">
            </div>
        </a>
        <div class="d-flex align-items-center ms-auto nav-icons" style="margin-right: 90px">
            <div class="user-greeting">Xin chào, <?php echo htmlspecialchars($nguoiDung['HoTen']); ?></div>
            <a href="Account/profile.php?id=<?php echo htmlspecialchars($nguoiDung['MaNguoiDung']); ?>" class="icon">
                <i class="fas fa-user"></i> Hồ sơ
            </a>
            <a href="../logout.php" class="icon">
                <i class="fas fa-sign-out"></i> Đăng xuất
            </a>
        </div>
    </div>

    <div class="container body-content">
        <!-- Thống kê tình trạng đơn hàng -->
        <div class="row mb-4 pt-2 mt-3">
            <?php if ($nguoiDung['VaiTro'] === 'AD'): ?>
                <div class="col-md-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <div>Đơn chờ xác nhận: <?php echo $thongKe['SLDonChoXacNhan']; ?></div>
                            <div class="fw-bold" style="font-size: 20px"><?php echo number_format($thongKe['GTDonChoXacNhan'], 0, ',', '.'); ?> đ</div>
                            <div style="text-align: right"><a href="Order/order.php?TinhTrang=0" style="color: white;">Chi tiết</a></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div>Đơn đang giao: <?php echo $thongKe['SLDonDangGiao']; ?></div>
                        <div class="fw-bold" style="font-size: 20px"><?php echo number_format($thongKe['GTDonDangGiao'], 0, ',', '.'); ?> đ</div>
                        <div style="text-align: right"><a href="Order/order.php?TinhTrang=1" style="color: white;">Chi tiết</a></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div>Đơn thành công: <?php echo $thongKe['SLDonThanhCong']; ?></div>
                        <div class="fw-bold" style="font-size: 20px"><?php echo number_format($thongKe['GTDonThanhCong'], 0, ',', '.'); ?> đ</div>
                        <div style="text-align: right"><a href="Order/order.php?TinhTrang=2" style="color: white;">Chi tiết</a></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div>Đơn đã hủy: <?php echo $thongKe['SLDonDaHuy']; ?></div>
                        <div class="fw-bold" style="font-size: 20px"><?php echo number_format($thongKe['GTDonDaHuy'], 0, ',', '.'); ?> đ</div>
                        <div style="text-align: right"><a href="Order/order.php?TinhTrang=3" style="color: white;">Chi tiết</a></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ sản phẩm bán chạy và doanh thu -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-lg pb-2">
                    <div class="card-header brand-header text-center">Top 5 sản phẩm bán chạy</div>
                    <div class="card-body">
                        <canvas id="spChart"></canvas>
                        <div style="text-align: right" class="pe-2 pt-2"><a href="Statistics/top_products.php">Xem thêm</a></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-lg pb-2">
                    <div class="card-header brand-header text-center">Doanh thu 6 tháng gần nhất</div>
                    <div class="card-body">
                        <canvas id="thangChart"></canvas>
                        <div style="text-align: right" class="pe-2 pt-2"><a href="Statistics/revenue_by_period.php">Xem thêm</a></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doanh thu theo sản phẩm và hãng sản xuất -->
        <div class="container">
            <div class="card shadow-lg pb-4 mt-4" style="max-width: 100%; margin: 0 auto;">
                <div class="card-header brand-header text-center fw-bold">
                    Doanh thu theo sản phẩm và hãng sản xuất
                </div>
                <div class="card-body" id="product-revenue">
                    <form method="GET" action="admin.php" class="mb-4" id="search-form">
                        <div class="table-responsive">
                            <table class="table1" align="center">
                                <tr>
                                    <th style="padding-left: 10px; padding-right: 10px;">Từ ngày</th>
                                    <th style="padding-left: 10px; padding-right: 10px;">Đến ngày</th>
                                    <th colspan="<?php echo count($hsxOptions); ?>" style="padding-left: 10px; padding-right: 10px;">Hãng sản xuất</th>
                                    <th colspan="2" style="padding-left: 10px; padding-right: 10px;"></th>
                                </tr>
                                <tr>
                                    <td style="padding-left: 10px; padding-right: 10px;">
                                        <input type="date" name="tuNgay" value="<?php echo htmlspecialchars($tuNgay ?? ''); ?>" class="form-control" />
                                    </td>
                                    <td style="padding-left: 10px; padding-right: 10px;">
                                        <input type="date" name="denNgay" value="<?php echo htmlspecialchars($denNgay ?? ''); ?>" class="form-control" />
                                    </td>
                                    <?php foreach ($hsxOptions as $hsx): ?>
                                        <td style="padding-left: 10px; padding-right: 10px;">
                                            <input class="form-check-input" type="checkbox" name="HSX[]" value="<?php echo htmlspecialchars($hsx['MaHSX']); ?>" 
                                                id="hsx_<?php echo htmlspecialchars($hsx['MaHSX']); ?>" 
                                                <?php echo in_array($hsx['MaHSX'], $selectedHSX) ? 'checked' : ''; ?> 
                                                style="transform: scale(1.3); margin-right: 5px" />
                                            <label class="form-check-label" for="hsx_<?php echo htmlspecialchars($hsx['MaHSX']); ?>">
                                                <?php echo htmlspecialchars($hsx['TenHSX']); ?>
                                            </label>
                                        </td>
                                    <?php endforeach; ?>
                                    <td>
                                        <button type="reset" class="btn1 me-1" id="reset-button">Nhập lại</button>
                                        <button type="submit" class="btn2">Thống kê</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered text-center align-middle mb-0">
                            <thead style="font-weight: bold">
                                <tr>
                                    <th>STT</th>
                                    <th>Mã sản phẩm</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Số lượng bán</th>
                                    <th>Giá bán</th>
                                    <th>Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groupedData as $hsx): ?>
                                    <?php $stt = 1; ?>
                                    <tr class="table fw-bold border-none" style="background-color: #ffd1a6">
                                        <td colspan="5" class="text-start">
                                            <?php echo strtoupper($hsx['TenHSX']); ?>
                                        </td>
                                        <td class="text-end" style="color: red">
                                            <?php echo number_format($hsx['TongTien'], 0, ',', '.'); ?> đ
                                        </td>
                                    </tr>
                                    <?php foreach ($hsx['SanPhams'] as $sp): ?>
                                        <tr>
                                            <td><?php echo $stt++; ?></td>
                                            <td><?php echo htmlspecialchars($sp['MaSP']); ?></td>
                                            <td><?php echo htmlspecialchars($sp['TenSP']); ?></td>
                                            <td><?php echo $sp['TongSoLuong']; ?></td>
                                            <td><?php echo number_format($sp['GiaBan'], 0, ',', '.'); ?> đ</td>
                                            <td class="text-end fw-bold"><?php echo number_format($sp['TongTien'], 0, ',', '.'); ?> đ</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                                <tr class="fw-bold" style="background-color: #ffd1a6">
                                    <td colspan="5" class="text-end" style="color: red">TỔNG DOANH THU:</td>
                                    <td class="text-end" style="color: red"><?php echo number_format($tongDoanhThu, 0, ',', '.'); ?> đ</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Vẽ biểu đồ top sản phẩm
        const spLabels = <?php echo $spLabels; ?>;
        const spData = <?php echo $spData; ?>;
        const ctxSP = document.getElementById('spChart').getContext('2d');
        new Chart(ctxSP, {
            type: 'bar',
            data: {
                labels: spLabels,
                datasets: [{
                    label: 'Số lượng đặt mua',
                    data: spData,
                    backgroundColor: '#fd710d'
                }]
            }
        });

        // Vẽ biểu đồ doanh thu theo tháng
        const thangLabels = <?php echo $thangLabels; ?>;
        const doanhThuData = <?php echo $doanhThuData; ?>;
        const ctxThang = document.getElementById('thangChart').getContext('2d');
        new Chart(ctxThang, {
            type: 'line',
            data: {
                labels: thangLabels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: doanhThuData,
                    borderColor: '#fe6161',
                }]
            }
        });

        // Tự di chuyển đến mục doanh thu theo sản phẩm khi tìm kiếm
        document.addEventListener("DOMContentLoaded", function () {
            if (window.location.search.length > 0) {
                setTimeout(() => {
                    document.getElementById("product-revenue").scrollIntoView({ behavior: "smooth" });
                }, 0);
            }
        });

        // Reset form tìm kiếm
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("reset-button").addEventListener("click", function (event) {
                event.preventDefault();
                let form = document.getElementById("search-form");
                form.querySelectorAll("input").forEach(input => {
                    if (input.type === "date") {
                        input.value = "";
                    }
                    if (input.type === "checkbox") {
                        input.checked = false;
                    }
                });
                window.history.replaceState(null, null, window.location.pathname);
            });
        });
    </script>
</body>
</html>
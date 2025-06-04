<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['AD', 'NV'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

$nguoiDung = $_SESSION['user'];

// Lấy tham số từ form
$kieuKy = isset($_GET['kieuKy']) ? $_GET['kieuKy'] : 'Thang';
$soKy = isset($_GET['soKy']) ? (int)$_GET['soKy'] : 6;

// Ngày hôm nay
$today = new DateTime();

// Danh sách kỳ cần thống kê
$danhSachKy = [];
for ($i = 0; $i < $soKy; $i++) {
    $start = null;
    $end = null;
    $label = '';

    switch ($kieuKy) {
        case 'Nam':
            $year = (clone $today)->modify("-$i years")->format('Y');
            $start = new DateTime("$year-01-01");
            $end = new DateTime("$year-12-31");
            $label = "Năm $year";
            break;

        case 'Quy':
            $currentQ = ceil((int)$today->format('n') / 3);
            $qDate = (clone $today)->modify("-$i months");
            $q = ceil((int)$qDate->format('n') / 3);
            $qYear = $qDate->format('Y');
            $start = new DateTime("{$qYear}-" . (($q - 1) * 3 + 1) . "-01");
            $end = (clone $start)->modify('+2 months')->modify('last day of this month');
            $label = "Q$q/$qYear";
            break;

        case 'Tuan':
            $startOfWeek = (clone $today)->modify("-$i weeks - " . ((int)$today->format('w') - 1) . " days");
            $start = clone $startOfWeek;
            $end = (clone $start)->modify('+6 days');
            $label = "Tuần {$start->format('d/m')} - {$end->format('d/m')}";
            break;

        case 'Ngay':
            $day = (clone $today)->modify("-$i days");
            $start = clone $day;
            $end = clone $day;
            $label = $day->format('d/m');
            break;

        default: // 'Thang'
            $monthDate = (clone $today)->modify("-$i months");
            $start = new DateTime("{$monthDate->format('Y')}-{$monthDate->format('m')}-01");
            $end = (clone $start)->modify('+1 month -1 day');
            $label = "{$monthDate->format('m')}/{$monthDate->format('Y')}";
            break;
    }
    $danhSachKy[] = ['label' => $label, 'start' => $start, 'end' => $end];
}

// Đảo ngược để hiển thị theo thứ tự tăng dần
$danhSachKy = array_reverse($danhSachKy);

// Lấy dữ liệu đơn hàng từ database
$sql = "SELECT Ngay, SUM(ct.SoLuong * sp.GiaBan) as TongTien 
        FROM DonDatHang ddh 
        JOIN ChiTietDDH ct ON ddh.MaDDH = ct.MaDDH 
        JOIN SanPham sp ON ct.MaSP = sp.MaSP 
        WHERE ddh.TinhTrang = 2 
        GROUP BY ddh.MaDDH, ddh.Ngay";
$result = mysqli_query($conn, $sql);
$donHoanThanh = [];
while ($row = mysqli_fetch_assoc($result)) {
    $donHoanThanh[] = [
        'Ngay' => new DateTime($row['Ngay']),
        'TongTien' => $row['TongTien']
    ];
}

// Tính doanh thu theo từng kỳ
$ketQua = [];
foreach ($danhSachKy as $ky) {
    $doanhThu = 0;
    foreach ($donHoanThanh as $don) {
        if ($don['Ngay'] >= $ky['start'] && $don['Ngay'] <= $ky['end']) {
            $doanhThu += $don['TongTien'];
        }
    }
    $ketQua[] = ['Label' => $ky['label'], 'DoanhThu' => $doanhThu];
}

// Chuẩn bị dữ liệu cho view
$thangLabels = json_encode(array_column($ketQua, 'Label'));
$doanhThuData = json_encode(array_column($ketQua, 'DoanhThu'));

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue by Period</title>
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
            <div class="user-greeting">Xin chào, <?php echo htmlspecialchars($nguoiDung['HoTen']); ?></div>
            <a href="../Account/profile.php?id=<?php echo htmlspecialchars($nguoiDung['MaNguoiDung']); ?>" class="icon">
                <i class="fas fa-user"></i> Hồ sơ
            </a>
            <a href="../../logout.php" class="icon">
                <i class="fas fa-sign-out"></i> Đăng xuất
            </a>
        </div>
    </div>

    <div class="container body-content">
        <div class="card shadow-lg pb-4 mt-4">
            <div class="card-header brand-header text-center">Thống kê doanh thu theo kỳ</div>
            <div class="card-body">
                <form method="GET" action="revenue_by_period.php" class="row g-3 mb-4" id="search-form">
                    <div class="col-md-3"></div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Kỳ thống kê</label>
                        <select name="kieuKy" class="form-select">
                            <option value="Nam" <?php echo ($kieuKy === 'Nam') ? 'selected' : ''; ?>>Năm</option>
                            <option value="Quy" <?php echo ($kieuKy === 'Quy') ? 'selected' : ''; ?>>Quý</option>
                            <option value="Thang" <?php echo ($kieuKy === 'Thang') ? 'selected' : ''; ?>>Tháng</option>
                            <option value="Tuan" <?php echo ($kieuKy === 'Tuan') ? 'selected' : ''; ?>>Tuần</option>
                            <option value="Ngay" <?php echo ($kieuKy === 'Ngay') ? 'selected' : ''; ?>>Ngày</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Số kỳ</label>
                        <input type="number" name="soKy" value="<?php echo htmlspecialchars($soKy); ?>" min="1" max="12" class="form-control" />
                    </div>
                    <div class="col-md-2 col-md-2 d-flex align-items-end justify-content-center gap-2">
                        <button type="reset" class="btn1" id="reset-button">Nhập lại</button>
                        <button type="submit" class="btn2">Thống kê</button>
                    </div>
                    <div class="col-md-3"></div>
                </form>
                <canvas id="thangChart"></canvas>
                <div style="text-align: right" class="pe-2 pt-2"><a href="../admin.php">Quay lại</a></div>
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
        // Reset form
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("reset-button").addEventListener("click", function (event) {
                event.preventDefault();
                let form = document.getElementById("search-form");
                form.querySelectorAll("input").forEach(input => {
                    if (input.type === "number") {
                        input.value = 6;
                    }
                });
                let dropdown = form.querySelector("select[name='kieuKy']");
                if (dropdown) dropdown.selectedIndex = 2; // Chọn "Thang" (tháng)
                window.history.replaceState(null, null, window.location.pathname);
            });
        });

        // Vẽ biểu đồ doanh thu theo kỳ
        const thangLabels = <?php echo $thangLabels; ?>;
        const doanhThuData = <?php echo $doanhThuData; ?>;
        const ctxThang = document.getElementById('thangChart').getContext('2d');
        new Chart(ctxThang, {
            type: 'line',
            data: {
                labels: thangLabels,
                datasets: [{
                    label: 'Doanh thu',
                    data: doanhThuData,
                    borderColor: '#fe6161',
                }]
            }
        });
    </script>
</body>
</html>
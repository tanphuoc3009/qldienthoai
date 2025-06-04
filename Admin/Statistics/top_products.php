<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['AD', 'NV'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

$nguoiDung = $_SESSION['user'];

// Lấy tham số từ form
$tuNgay = isset($_GET['tuNgay']) ? $_GET['tuNgay'] : null;
$denNgay = isset($_GET['denNgay']) ? $_GET['denNgay'] : null;
$maHSX = isset($_GET['maHSX']) ? $_GET['maHSX'] : null;
$kieuThongKe = isset($_GET['kieuThongKe']) && $_GET['kieuThongKe'] === 'true';
$soLuong = isset($_GET['sl']) && is_numeric($_GET['sl']) ? (int)$_GET['sl'] : 5;

// Hàm thống kê top sản phẩm bán chạy
function sanPhamBanChay($conn, $tuNgay, $denNgay, $maHSX, $kieuThongKe, $soLuong) {
    $sql = "SELECT sp.TenSP, sp.MaSP, sp.GiaBan, ";
    $sql .= $kieuThongKe ? "SUM(ct.SoLuong * sp.GiaBan) as GiaTri" : "SUM(ct.SoLuong) as SoLuong";
    $sql .= " FROM ChiTietDDH ct 
              JOIN DonDatHang ddh ON ct.MaDDH = ddh.MaDDH 
              JOIN SanPham sp ON ct.MaSP = sp.MaSP ";
    if ($maHSX) {
        $sql .= "JOIN HangSanXuat hsx ON sp.MaHSX = hsx.MaHSX ";
    }
    $sql .= "WHERE ddh.TinhTrang < 3 ";
    if ($tuNgay) {
        $sql .= " AND ddh.Ngay >= ?";
    }
    if ($denNgay) {
        $sql .= " AND ddh.Ngay <= ?";
    }
    if ($maHSX) {
        $sql .= " AND sp.MaHSX = ?";
    }
    $sql .= " GROUP BY ct.MaSP, sp.TenSP, sp.GiaBan ";
    $sql .= $kieuThongKe ? "ORDER BY GiaTri DESC " : "ORDER BY SoLuong DESC ";
    $sql .= "LIMIT ?";

    $params = [];
    $types = '';
    if ($tuNgay) {
        $params[] = $tuNgay;
        $types .= 's';
    }
    if ($denNgay) {
        $params[] = $denNgay;
        $types .= 's';
    }
    if ($maHSX) {
        $params[] = $maHSX;
        $types .= 's';
    }
    $params[] = $soLuong;
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $topSanPham = [];
    while ($row = $result->fetch_assoc()) {
        $topSanPham[] = $row;
    }
    $stmt->close();

    return [
        'labels' => array_column($topSanPham, 'TenSP'),
        'data' => array_column($topSanPham, $kieuThongKe ? 'GiaTri' : 'SoLuong')
    ];
}

// Thống kê sản phẩm bán chạy
$topSanPham = sanPhamBanChay($conn, $tuNgay, $denNgay, $maHSX, $kieuThongKe, $soLuong);
$spLabels = json_encode($topSanPham['labels'], JSON_UNESCAPED_UNICODE);
$spData = json_encode($topSanPham['data']);

// Lấy danh sách hãng sản xuất
$sqlHSX = "SELECT MaHSX, TenHSX FROM HangSanXuat";
$hsxList = mysqli_query($conn, $sqlHSX);
$hsxOptions = [];
while ($row = mysqli_fetch_assoc($hsxList)) {
    $hsxOptions[] = $row;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Products</title>
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
            <div class="card-header brand-header text-center">Thống kê sản phẩm bán chạy</div>
            <div class="card-body">
                <form method="GET" action="top_products.php" class="row g-3 mb-4" id="search-form">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Từ ngày</label>
                        <input type="date" name="tuNgay" value="<?php echo htmlspecialchars($tuNgay ?? ''); ?>" class="form-control" style="width: 100%;">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Đến ngày</label>
                        <input type="date" name="denNgay" value="<?php echo htmlspecialchars($denNgay ?? ''); ?>" class="form-control" style="width: 100%;">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Hãng sản xuất</label>
                        <select name="maHSX" class="form-select">
                            <?php foreach ($hsxOptions as $hsx): ?>
                                <option value="<?php echo htmlspecialchars($hsx['MaHSX']); ?>" <?php echo ($maHSX === $hsx['MaHSX']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($hsx['TenHSX']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Kiểu thống kê</label>
                        <select name="kieuThongKe" class="form-select">
                            <option value="false" <?php echo (!$kieuThongKe) ? 'selected' : ''; ?>>Số lượng</option>
                            <option value="true" <?php echo ($kieuThongKe) ? 'selected' : ''; ?>>Doanh số</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Số lượng sản phẩm</label>
                        <input type="number" name="sl" value="<?php echo htmlspecialchars($soLuong); ?>" min="1" max="10" class="form-control" style="width: 100%;">
                    </div>
                    <div class="col-md-2 d-flex align-items-end justify-content-center gap-2">
                        <button type="reset" class="btn1" id="reset-button">Nhập lại</button>
                        <button type="submit" class="btn2">Thống kê</button>
                    </div>
                </form>
                <canvas id="spChart"></canvas>
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
                    if (input.type === "date" || input.type === "text") {
                        input.value = "";
                    }
                    if (input.type === "number") {
                        input.value = 5;
                    }
                });
                let dropdown = form.querySelector("select[name='maHSX']");
                if (dropdown) dropdown.selectedIndex = 0;
                let kieuTK = form.querySelector("select[name='kieuThongKe']");
                if (kieuTK) kieuTK.selectedIndex = 0;
                window.history.replaceState(null, null, window.location.pathname);
            });
        });

        // Vẽ biểu đồ top sản phẩm
        const spLabels = <?php echo $spLabels; ?>;
        const spData = <?php echo $spData; ?>;
        const ctxSP = document.getElementById('spChart').getContext('2d');
        new Chart(ctxSP, {
            type: 'bar',
            data: {
                labels: spLabels,
                datasets: [{
                    label: '<?php echo $kieuThongKe ? "Doanh số đặt mua" : "Số lượng đặt mua"; ?>',
                    data: spData,
                    backgroundColor: '#fd710d'
                }]
            }
        });
    </script>
</body>
</html>
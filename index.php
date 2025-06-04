<?php
require 'config.php';

// Lấy tham số tìm kiếm từ GET
$maSP = isset($_GET['MaSP']) ? $_GET['MaSP'] : '';
$tenSP = isset($_GET['TenSP']) ? $_GET['TenSP'] : '';
$gbMin = isset($_GET['GBMin']) && is_numeric($_GET['GBMin']) ? (int)$_GET['GBMin'] : 0;
$gbMax = isset($_GET['GBMax']) && is_numeric($_GET['GBMax']) ? (int)$_GET['GBMax'] : 70000000;
$ram = isset($_GET['Ram']) ? (array)$_GET['Ram'] : [];
$dungLuong = isset($_GET['DungLuong']) ? (array)$_GET['DungLuong'] : [];
$hdh = isset($_GET['HDH']) ? $_GET['HDH'] : '';
$maHSX = isset($_GET['MaHSX']) ? $_GET['MaHSX'] : '';

// Chuẩn bị dữ liệu cho giao diện
$viewData = [
    'MaSP' => $maSP,
    'TenSP' => $tenSP,
    'GBMin' => $gbMin,
    'GBMax' => $gbMax,
    'Ram' => $ram,
    'DungLuong' => $dungLuong,
    'HDH' => $hdh,
    'MaHSX' => $maHSX
];

// Lấy danh sách hãng sản xuất cho dropdown
$hangSanXuats = [];
$result = mysqli_query($conn, "SELECT MaHSX, TenHSX FROM HangSanXuat");
if(mysqli_num_rows($result)!=0){
    while ($row = mysqli_fetch_assoc($result)) {
        $hangSanXuats[$row['MaHSX']] = $row['TenHSX'];
}
}

// Xây dựng truy vấn SQL với prepared statements
$sql = "SELECT ct.*, sp.TenSP, sp.GiaBan, sp.Ram, sp.DungLuong, sp.HeDieuHanh, sp.MaHSX, hsx.TenHSX
        FROM ChiTietSanPham ct
        JOIN SanPham sp ON ct.MaSP = sp.MaSP
        JOIN HangSanXuat hsx ON sp.MaHSX = hsx.MaHSX
        WHERE 1=1";
$params = [];
$types = '';

if (!empty($maSP)) {
    $sql .= " AND ct.MaSP LIKE ?";
    $params[] = "%$maSP%";
    $types .= 's';
}
if (!empty($tenSP)) {
    $sql .= " AND sp.TenSP LIKE ?";
    $params[] = "%$tenSP%";
    $types .= 's';
}
$sql .= " AND sp.GiaBan BETWEEN ? AND ?";
$params[] = $gbMin;
$params[] = $gbMax;
$types .= 'ii';
if (!empty($ram)) {
    $ram = array_map('intval', $ram);
    $placeholders = implode(',', array_fill(0, count($ram), '?'));
    $sql .= " AND sp.Ram IN ($placeholders)";
    $params = array_merge($params, $ram);
    $types .= str_repeat('i', count($ram));
}
if (!empty($dungLuong)) {
    $dungLuong = array_map('intval', $dungLuong);
    $placeholders = implode(',', array_fill(0, count($dungLuong), '?'));
    $sql .= " AND sp.DungLuong IN ($placeholders)";
    $params = array_merge($params, $dungLuong);
    $types .= str_repeat('i', count($dungLuong));
}
if (!empty($hdh)) {
    $sql .= " AND sp.HeDieuHanh LIKE ?";
    $params[] = "%$hdh%";
    $types .= 's';
}
if (!empty($maHSX)) {
    $sql .= " AND sp.MaHSX = ?";
    $params[] = $maHSX;
    $types .= 's';
}

// Chuẩn bị và thực thi truy vấn
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Nhóm sản phẩm theo hãng sản xuất
$danhMuc = [];
while ($row = $result->fetch_assoc()) {
    $danhMuc[$row['MaHSX']][] = $row;
}
$stmt->close();

if (empty($danhMuc)) {
    $viewData['TB'] = "Không có sản phẩm tìm kiếm!";
}

// Xử lý thông tin người dùng
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . $_SESSION['user']['HoTen'] : "";

// Số lượng mặt hàng trong giỏ
$slMatHang = isset($_SESSION['SLMatHang']) ? $_SESSION['SLMatHang'] : 0;

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ</title>
    <link rel="stylesheet" href="Content/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="Content/Style.css"/>
</head>
<body>
    <!-- Header -->
    <div id="header">
        <a href="index.php">
            <div class="logo">
                <img src="Images/Banner/logo.jpg" alt="Logo">
            </div>
        </a>
        <?php if (isset($_SESSION['user'])): ?>
            <div class="nav-icons" style="margin-right: 90px">
                <div class="user-greeting"><?php echo $nguoiDung ?></div>
                <a href="Customer/Cart/cart.php" class="icon">
                    <i class="fas fa-shopping-cart"></i> Giỏ hàng(<span id="SLMatHang"><?php echo $slMatHang; ?></span>)
                </a>
                <a href="Customer/Order/order.php" class="icon">
                    <i class="fas fa-receipt"></i> Đơn hàng
                </a>
                <a href="Customer/Account/profile.php?id=<?php echo $_SESSION['user']['MaNguoiDung'] ?>" class="icon">
                    <i class="fas fa-user"></i> Hồ sơ
                </a>
                <a href="logout.php" class="icon">
                    <i class="fas fa-sign-out"></i> Đăng xuất
                </a>
            </div>
        <?php else: ?>
            <a href="index.php">
                <h2 class="site-title">Siêu thị điện thoại Ego Mobile</h2>
            </a>
            <div class="nav-icons" style="margin-right: 90px">
                <a href="login.php" class="icon">
                    <i class="fas fa-user"></i> Đăng nhập
                </a>
                <a href="Customer/Account/register.php" class="icon">
                    <i class="fas fa-cog"></i> Đăng ký
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Content -->
    <div id="content">
        <!-- Banner -->
        <div class="container mt-5">
            <div class="row align-items-stretch">
                <!-- Ảnh bìa -->
                <div class="col-md-8">
                    <div class="h-100">
                        <img src="Images/Banner/banner.jpg" class="img-fluid w-100 h-100 object-fit-cover" />
                    </div>
                </div>
                <!-- Chính sách -->
                <div class="col-md-4 d-flex flex-column">
                    <div class="p-3 rounded flex-fill" style="background-color: #fac17e;">
                        <h5 class="fw-bold d-flex align-items-center">
                            <img src="Images/Icon/delivery.jpg" class="me-2" width="40" height="40">
                            Miễn phí vận chuyển
                        </h5>
                        <p class="mb-0">100% đơn hàng đều được miễn phí vận chuyển khi thanh toán trước</p>
                    </div>
                    <div class="p-3 rounded flex-fill mt-3" style="background-color: #f4b3a8;">
                        <h5 class="fw-bold d-flex align-items-center">
                            <img src="Images/Icon/description.jpg" class="me-2" width="40" height="40">
                            Bảo hành tận tâm
                        </h5>
                        <p class="mb-0">Luôn cam kết mang lại trải nghiệm bảo hành sản phẩm ưng ý nhất</p>
                    </div>
                    <div class="p-3 rounded flex-fill mt-3" style="background-color: #f6fd76;">
                        <h5 class="fw-bold d-flex align-items-center">
                            <img src="Images/Icon/exchange.jpg" class="me-2" width="40" height="40">
                            Đổi trả 1-1 hoặc hoàn tiền
                        </h5>
                        <p class="mb-0">Khi sản phẩm phát sinh lỗi và bạn cảm thấy sản phẩm không đáp ứng được nhu cầu</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Sản phẩm nổi bật -->
        <div class="container mt-5">
            <div class="card shadow-lg rounded-3 bg-white">
                <h2 class="card-header brand-header" style="text-align: center">SẢN PHẨM NỔI BẬT</h2>
                <div class="row text-center g-4">
                    <a class="product-link col-md-4" href="Customer/Product/details.php?id=SP001">
                        <img src="Images/Banner/spnb1.jpg" class="img-fluid rounded shadow; p-4">
                    </a>
                    <a class="product-link col-md-4" href="Customer/Product/details.php?id=SP002">
                        <img src="Images/Banner/spnb2.jpg" class="img-fluid rounded shadow; p-4">
                    </a>
                    <a class="product-link col-md-4" href="Customer/Product/details.php?id=SP003">
                        <img src="Images/Banner/spnb3.jpg" class="img-fluid rounded shadow; p-4">
                    </a>
                </div>
            </div>
        </div>
        <!-- Danh mục sản phẩm -->
        <div class="container product-section mt-5">
            <div class="row">
                <!-- Bộ lọc tìm kiếm -->
                <div class="col-md-3 filter-section">
                    <div id="product-list" class="card shadow-lg p-3">
                        <h5 class="brand-header">Tìm kiếm</h5>
                        <form id="search-form" method="GET" action="index.php">
                            <input type="hidden" name="MaSP" value="<?php echo $viewData['MaSP'] ?>">
                            <div class="mb-3">
                                <label class="form-label"><strong>Tên sản phẩm:</strong></label>
                                <input type="text" name="TenSP" class="form-control" value="<?php echo $viewData['TenSP'] ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Giá thấp nhất:</strong></label>
                                <input type="range" id="gbMin" name="GBMin" class="form-range" min="0" max="70000000" step="1000000"
                                       value="<?php echo $viewData['GBMin']; ?>" oninput="updateFormattedValues()">
                                <output id="minOutput"><?php echo number_format($viewData['GBMin'], 0, ',', '.'); ?></output>đ
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Giá cao nhất:</strong></label>
                                <input type="range" id="gbMax" name="GBMax" class="form-range" min="0" max="70000000" step="1000000"
                                       value="<?php echo $viewData['GBMax']; ?>" oninput="updateFormattedValues()">
                                <output id="maxOutput"><?php echo number_format($viewData['GBMax'], 0, ',', '.'); ?></output>đ
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>RAM (GB):</strong></label><br />
                                <div class="checkbox-group">
                                    <?php
                                    $ramOptions = [4, 6, 8, 12];
                                    $selectedRams = $viewData['Ram'];
                                    foreach ($ramOptions as $ram) {
                                        $checked = in_array($ram, $selectedRams) ? 'checked' : '';
                                        echo "<div style='display: inline-block; max-width: 64px; width: 100%'>";
                                        echo "<input class='form-check-input' type='checkbox' name='Ram[]' value='$ram' 
                                        id='ram_$ram' $checked style='transform: scale(1.3); margin-right: 10%'/>";
                                        echo "<label for='ram_$ram'>$ram</label>";
                                        echo "</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Dung lượng (GB):</strong></label><br />
                                <div class="checkbox-group">
                                    <?php
                                    $storageOptions = [64, 128, 256, 512];
                                    $selectedStorages = $viewData['DungLuong'];
                                    foreach ($storageOptions as $dungluong) {
                                        $checked = in_array($dungluong, $selectedStorages) ? 'checked' : '';
                                        echo "<div style='display: inline-block; max-width: 64px; width: 100%'>";
                                        echo "<input class='form-check-input' type='checkbox' name='DungLuong[]' value='$dungluong' 
                                        id='dl_$dungluong' $checked style='transform: scale(1.3); margin-right: 10%'/>";
                                        echo "<label for='dl_$dungluong'>$dungluong</label>";
                                        echo "</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Hệ điều hành:</strong></label>
                                <input type="text" name="HDH" class="form-control" value="<?php echo $viewData['HDH'] ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Hãng:</strong></label>
                                <select name="MaHSX" class="form-control">
                                    <option value="">Tất cả</option>
                                    <?php
                                    foreach ($hangSanXuats as $key => $value) {
                                        $selected = ($key == $viewData['MaHSX']) ? 'selected' : '';
                                        echo "<option value='$key' $selected>" . $value . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="text-center d-flex justify-content-center gap-2">
                                <button type="reset" class="btn1" id="reset-button">Nhập lại</button>
                                <button type="submit" class="btn2">Tìm kiếm</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Danh mục sản phẩm -->
                <div class="col-md-9 product-list">
                    <div class="card shadow-lg p-3">
                        <?php if (isset($viewData['TB'])): ?>
                            <div class="alert alert-warning"><?php echo $viewData['TB'] ?></div>
                        <?php endif; ?>
                        <?php foreach ($danhMuc as $maHSX => $group): ?>
                            <div class="brand-section">
                                <!-- Tiêu đề hãng -->
                                <div class="brand-header"><?php echo $group[0]['TenHSX'] ?></div>
                                <!-- Thông tin sản phẩm -->
                                <div class="product-container">
                                    <?php
                                    // Nhóm sản phẩm theo MaSP để tránh trùng lặp
                                    $productsByMaSP = [];
                                    foreach ($group as $item) {
                                        $productsByMaSP[$item['MaSP']][] = $item;
                                    }
                                    foreach ($productsByMaSP as $maSP => $items):
                                        $product = $items[0];
                                        $firstImage = $product['AnhBia'];
                                    ?>
                                        <div class="product-card">
                                            <a class="product-link" href="Customer/Product/product_details.php?id=<?php echo $product['MaSP']?>">
                                                <img src="Images/SanPham/<?php echo $firstImage?>" class="product-image" />
                                                <h3><?php echo $product['TenSP'] ?></h3>
                                                <p class="price"><?php echo number_format($product['GiaBan'], 0, ',', '.'); ?> đ</p>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>

    <script>
        // Hàm tự di chuyển đến mục sản phẩm khi người dùng tìm kiếm
        document.addEventListener("DOMContentLoaded", function () {
            if (window.location.search.length > 0) {
                setTimeout(() => {
                    document.getElementById("product-list").scrollIntoView({ behavior: "smooth" });
                }, 0);
            }
        });

        // Hàm định dạng số
        function formatNumber(n) {
            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Hàm reset thông tin các ô tìm kiếm
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("reset-button").addEventListener("click", function (event) {
                event.preventDefault();
                let form = document.getElementById("search-form");
                form.querySelectorAll("input").forEach(input => {
                    if (input.type === "text" || input.type === "number") {
                        input.value = "";
                    }
                    if (input.type === "checkbox") {
                        input.checked = false;
                    }
                    if (input.id === "gbMin") {
                        input.value = "0";
                        document.getElementById("minOutput").textContent = formatNumber(0);
                    }
                    if (input.id === "gbMax") {
                        input.value = "70000000";
                        document.getElementById("maxOutput").textContent = formatNumber(70000000);
                    }
                });
                let dropdown = document.querySelector("select[name='MaHSX']");
                if (dropdown) {
                    dropdown.selectedIndex = 0;
                }
                window.history.replaceState(null, null, window.location.pathname);
            });
        });

        // Hàm cập nhật giá trị khi người dùng kéo thanh giá
        function updateFormattedValues() {
            const min = document.getElementById("gbMin").value;
            const max = document.getElementById("gbMax").value;
            document.getElementById("minOutput").textContent = formatNumber(min);
            document.getElementById("maxOutput").textContent = formatNumber(max);
        }
    </script>
</body>
</html>

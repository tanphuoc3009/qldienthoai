<?php
require ('../config.php');

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['AD', 'NV'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

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
$sql = "SELECT sp.MaSP, sp.TenSP, sp.GiaBan, sp.Ram, sp.DungLuong, sp.HeDieuHanh, sp.MaHSX, hsx.TenHSX, ct.AnhBia
        FROM SanPham sp
        LEFT JOIN ChiTietSanPham ct ON sp.MaSP = ct.MaSP
        LEFT JOIN HangSanXuat hsx ON sp.MaHSX = hsx.MaHSX
        WHERE 1=1";
$params = [];
$types = '';

if (!empty($maSP)) {
    $sql .= " AND sp.MaSP LIKE ?";
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
$sql .= " GROUP BY sp.MaSP, sp.TenSP, sp.GiaBan, sp.Ram, sp.DungLuong, sp.HeDieuHanh, sp.MaHSX, hsx.TenHSX";

// Phân trang
if (empty($_GET['page'])) {
    $_GET['page'] = 1;
}
$page = (int)$_GET['page'];
$rowsPerPage = 10;
$offset = ($page - 1) * $rowsPerPage;

// Đếm tổng số sản phẩm
$countStmt = $conn->prepare($sql);
if (!$countStmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = mysqli_num_rows($countResult);
$countStmt->close();

// Đếm tổng số trang
$maxPage = ceil($totalRows / $rowsPerPage);

// Lấy dữ liệu sản phẩm theo phân trang
$sql .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $rowsPerPage;
$types .= 'ii';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$sanPhams = [];
while ($row = $result->fetch_assoc()) {
    $sanPhams[] = $row;
}
$stmt->close();

// Xử lý thông tin người dùng
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . htmlspecialchars($_SESSION['user']['HoTen']) : "";
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm</title>
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
                        <a class="dropdown-item" href="Order/order.php">
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

    <!-- Phần tìm kiếm sản phẩm -->
    <div class="container mt-4 mx-auto" style="width: 830px">
        <div class="card shadow-lg pb-4">
            <div class="card-header brand-header text-center">
                Tìm kiếm sản phẩm
            </div>
            <div class="card-body">
                <form id="search-form" method="GET" action="admin.php">
                    <table class="table1" align="center" cellpadding="8" style="width: 100%;">
                        <tr>
                            <td><strong>Mã sản phẩm:</strong></td>
                            <td>
                                <input type="text" name="MaSP" class="form-control" style="width: 100%;" 
                                    value="<?php echo $viewData['MaSP']; ?>">
                            </td>
                            <td><strong>Tên sản phẩm:</strong></td>
                            <td>
                                <input type="text" name="TenSP" class="form-control" style="width: 100%;" 
                                    value="<?php echo $viewData['TenSP']; ?>">
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Giá thấp nhất:</strong></td>
                            <td>
                                <input type="range" id="gbMin" name="GBMin" class="form-range" 
                                    min="0" max="70000000" step="1000000" value="<?php echo $viewData['GBMin']; ?>" 
                                    oninput="updateFormattedValues()">
                                <output id="minOutput"><?php echo number_format($viewData['GBMin'], 0, ',', '.'); ?></output>đ
                            </td>
                            <td><strong>Giá cao nhất:</strong></td>
                            <td>
                                <input type="range" id="gbMax" name="GBMax" class="form-range" 
                                    min="0" max="70000000" step="1000000" value="<?php echo $viewData['GBMax']; ?>" 
                                    oninput="updateFormattedValues()">
                                <output id="maxOutput"><?php echo number_format($viewData['GBMax'], 0, ',', '.'); ?></output>đ
                            </td>
                        </tr>
                        <tr>
                            <td><strong>RAM:</strong></td>
                            <td colspan="3">
                                <?php
                                $ramOptions = [4, 6, 8, 12];
                                $selectedRams = $viewData['Ram'];
                                foreach ($ramOptions as $ram) {
                                    $checked = in_array($ram, $selectedRams) ? 'checked' : '';
                                    echo "<div style='display: inline-block; width: 15%;'>";
                                    echo "<input class='form-check-input' type='checkbox' name='Ram[]' value='$ram' 
                                    id='ram_$ram' $checked style='transform: scale(1.3); margin-right: 10%;'>";
                                    echo "<label for='ram_$ram'>$ram GB</label>";
                                    echo "</div>";
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Dung lượng:</strong></td>
                            <td colspan="3">
                                <?php
                                $storageOptions = [64, 128, 256, 512];
                                $selectedStorages = $viewData['DungLuong'];
                                foreach ($storageOptions as $dungluong) {
                                    $checked = in_array($dungluong, $selectedStorages) ? 'checked' : '';
                                    echo "<div style='display: inline-block; width: 15%;'>";
                                    echo "<input class='form-check-input' type='checkbox' name='DungLuong[]' value='$dungluong' 
                                    id='dl_$dungluong' $checked style='transform: scale(1.3); margin-right: 10%;'>";
                                    echo "<label for='dl_$dungluong'>$dungluong GB</label>";
                                    echo "</div>";
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Hệ điều hành:</strong></td>
                            <td>
                                <input type="text" name="HDH" class="form-control" style="width: 100%;" 
                                    value="<?php echo $viewData['HDH']; ?>">
                            </td>
                            <td><strong>Hãng sản xuất:</strong></td>
                            <td>
                                <select name="MaHSX" class="form-control" style="width: 100%;">
                                    <option value="">Tất cả</option>
                                    <?php
                                    foreach ($hangSanXuats as $key => $value) {
                                        $selected = ($key == $viewData['MaHSX']) ? 'selected' : '';
                                        echo "<option value='$key' $selected>" . $value . "</option>";
                                    }
                                    ?>
                                </select>
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

    <!-- Phần danh sách sản phẩm -->
    <div class="container mt-4">
        <div class="card shadow-lg pb-4" style="max-width: 100%; margin: 0 auto;" id="product-list">
            <div class="card-header brand-header text-center">
                Danh sách sản phẩm
            </div>
            <div class="card-body">
                <?php if ($vaiTro === 'AD'): ?>
                    <div class="d-flex justify-content-end mb-3">
                        <a href="Product/product_create.php" class="btn1">Thêm mới</a>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-center align-middle">
                        <thead style="font-weight: bold;">
                            <tr>
                                <th>STT</th>
                                <th>Mã sản phẩm</th>
                                <th>Tên sản phẩm</th>
                                <th>Hình ảnh</th>
                                <th>Giá bán</th>
                                <th>Hãng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = ($page - 1) * $rowsPerPage;
                            foreach ($sanPhams as $sp):
                                $i++;
                            ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo $sp['MaSP']; ?></td>
                                    <td><?php echo $sp['TenSP']; ?></td>
                                    <td>
                                        <img src="../Images/SanPham/<?php echo $sp['AnhBia']; ?>" 
                                                class="rounded" style="width: 80px; height: 80px; object-fit: cover;">
                                    </td>
                                    <td><?php echo number_format($sp['GiaBan'], 0, ',', '.'); ?> đ</td>
                                    <td><?php echo $sp['TenHSX']; ?></td>
                                    <td>
                                        <a href="Product/product_details.php?id=<?php echo $sp['MaSP']; ?>&action=detail"
                                            class="btn" style="background: #fd710d; color: white; border-radius: 5px; margin-right: 5px;">Xem</a>
                                        <?php if ($vaiTro === 'AD'): ?>
                                            <a href="Product/product_details.php?id=<?php echo $sp['MaSP']; ?>&action=edit"
                                                class="btn btn-warning" style="color: white; border-radius: 5px; margin-right: 5px;">Sửa</a>
                                            <a href="Product/product_details.php?id=<?php echo $sp['MaSP']; ?>&action=delete" 
                                                class="btn btn-danger" style="border-radius: 5px;">Xóa</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Phân trang -->
            <?php
            function buildQueryString($page) {
                $params = $_GET;
                $params['page'] = $page;
                return http_build_query($params);
            }
            ?>
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
                <a href="admin.php">Về trang chủ</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tự cuộn đến danh sách khi tìm kiếm
        document.addEventListener("DOMContentLoaded", function () {
            if (window.location.search.length > 0) {
                setTimeout(() => {
                    document.getElementById("product-list").scrollIntoView({ behavior: "smooth" });
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

        // Cập nhật giá trị thanh kéo
        function updateFormattedValues() {
            const min = document.getElementById("gbMin").value;
            const max = document.getElementById("gbMax").value;
            document.getElementById("minOutput").textContent = formatNumber(min);
            document.getElementById("maxOutput").textContent = formatNumber(max);
        }
    </script>
</body>
</html>
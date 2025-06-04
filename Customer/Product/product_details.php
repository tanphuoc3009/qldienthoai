<?php
require ('../../config.php');

// Lấy MaSP từ URL
$maSP = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($maSP)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Truy vấn chi tiết sản phẩm
$sql = "SELECT ct.MaSP, ct.MaMau, ct.AnhBia, ms.TenMau, 
               sp.TenSP, sp.GiaBan, sp.Ram, sp.DungLuong, sp.HeDieuHanh, sp.Mota, sp.MaHSX, 
               hsx.TenHSX
        FROM ChiTietSanPham ct
        JOIN SanPham sp ON ct.MaSP = sp.MaSP
        JOIN HangSanXuat hsx ON sp.MaHSX = hsx.MaHSX
        JOIN MauSac ms ON ct.MaMau = ms.MaMau
        WHERE ct.MaSP = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}

$stmt->bind_param("s", $maSP);
$stmt->execute();
$result = $stmt->get_result();
$chiTietSanPhams = [];
if ($result->num_rows != 0) {
    while ($row = $result->fetch_assoc()) {
        $chiTietSanPhams[] = $row;
    }
} else {
    header("HTTP/1.1 404 Not Found");
    die("Không tìm thấy sản phẩm!");
}
$stmt->close();

// Lấy thông tin sản phẩm đầu tiên
$sanPham = $chiTietSanPhams[0];

// Xử lý thông tin người dùng
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . $_SESSION['user']['HoTen'] : "";

// Số lượng mặt hàng trong giỏ
$slMatHang = isset($_SESSION['SLMatHang']) ? $_SESSION['SLMatHang'] : 0;

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết sản phẩm</title>
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
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['VaiTro'] === 'KH'): ?>
            <div class="nav-icons" style="margin-right: 90px">
                <div class="user-greeting"><?php echo $nguoiDung; ?></div>
                <a href="../Cart/cart.php" class="icon">
                    <i class="fas fa-shopping-cart"></i> Giỏ hàng(<span id="cart-count"><?php echo $slMatHang; ?></span>)
                </a>
                <a href="../Order/order.php" class="icon">
                    <i class="fas fa-receipt"></i> Đơn hàng
                </a>
                <a href="../Account/profile.php?id=<?php echo $_SESSION['user']['MaNguoiDung']; ?>" class="icon">
                    <i class="fas fa-user"></i> Hồ sơ
                </a>
                <a href="../../logout.php" class="icon">
                    <i class="fas fa-sign-out"></i> Đăng xuất
                </a>
            </div>
        <?php else: ?>
            <a href="../../index.php">
                <h2 class="site-title">Siêu thị điện thoại Ego Mobile</h2>
            </a>
            <div class="nav-icons" style="margin-right: 90px">
                <a href="../../login.php" class="icon">
                    <i class="fas fa-user"></i> Đăng nhập
                </a>
                <a href="../Account/register.php" class="icon">
                    <i class="fas fa-cog"></i> Đăng ký
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Content -->
    <div class="container mt-4">
        <div class="card shadow-lg pb-4" style="max-width: 90%; margin: 0 auto;">
            <div class="card-header brand-header" style="text-align: center; margin-bottom: 20px">Chi tiết sản phẩm</div>
            <div class="row" style="padding-right: 30px">
                <!-- Hình ảnh sản phẩm -->
                <div class="col-md-6 text-center">
                    <div class="d-flex mt-3 justify-content-center">
                        <img id="product-image" src="../../Images/SanPham/<?php echo $chiTietSanPhams[0]['AnhBia']; ?>" 
                             alt="<?php echo $sanPham['TenSP']; ?>" class="img-fluid" style="width: 300px; height: 300px;">
                    </div>
                    <h3 class="mt-3" style="font-size: 20px">Chọn màu sắc:</h3>
                    <div class="color-options d-flex justify-content-center">
                        <?php foreach ($chiTietSanPhams as $item): ?>
                            <button class="btn btn-outline-dark m-1 color-btn" 
                                    data-maMau="<?php echo $item['MaMau']; ?>" 
                                    data-image="<?php echo $item['AnhBia']; ?>" 
                                    style="width: 12%">
                                <?php echo $item['TenMau']; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Thông tin sản phẩm -->
                <div class="col-md-6">
                    <h2 class="text-center"><?php echo $sanPham['TenSP']; ?></h2>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item" style="font-size: 18px">
                            <strong>Hãng:</strong> <?php echo $sanPham['TenHSX']; ?>
                        </li>
                        <li class="list-group-item" style="font-size: 18px">
                            <strong>Giá bán:</strong> 
                            <span class="price" style="font-size: 18px"><?php echo number_format($sanPham['GiaBan'], 0, ',', '.'); ?> đ</span>
                        </li>
                        <li class="list-group-item" style="font-size: 18px">
                            <strong>RAM:</strong> <?php echo $sanPham['Ram']; ?> GB
                        </li>
                        <li class="list-group-item" style="font-size: 18px">
                            <strong>Dung lượng:</strong> <?php echo $sanPham['DungLuong']; ?> GB
                        </li>
                        <li class="list-group-item" style="font-size: 18px">
                            <strong>Hệ điều hành:</strong> <?php echo $sanPham['HeDieuHanh']; ?>
                        </li>
                        <li class="list-group-item" style="font-size: 18px">
                            <strong>Mô tả:</strong> <?php echo $sanPham['Mota'] ?: 'Không có mô tả.'; ?>
                        </li>
                    </ul>
                    <?php if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH'): ?>
                        <div class="d-flex justify-content-center gap-2 mt-4 mb-3">
                            <a href="../../login.php" class="btn1">Mua ngay</a>
                            <a href="../../login.php" class="btn2">Thêm giỏ hàng</a>
                        </div>
                    <?php else: ?>
                        <div class="text-center d-flex justify-content-center gap-2 mt-4 mb-3">
                            <button id="MuaNgay" class="btn1">Mua ngay</button>
                            <button id="ThemVaoGioHang" class="btn2">Thêm giỏ hàng</button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <a href="../../index.php">Về trang chủ</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        let buttons = document.querySelectorAll(".color-btn");
        let image = document.getElementById("product-image");
        let cartCount = document.getElementById("cart-count");

        // Chọn màu đầu tiên mặc định
        if (buttons.length > 0) {
            let defaultColor = buttons[0];
            let newImage = defaultColor.getAttribute("data-image");
            image.src = "../../Images/SanPham/" + newImage;
            defaultColor.classList.add("active");
        }

        // Xử lý chọn màu
        buttons.forEach(button => {
            button.addEventListener("click", function () {
                let newImage = this.getAttribute("data-image");
                image.src = "../../Images/SanPham/" + newImage;
                buttons.forEach(btn => btn.classList.remove("active"));
                this.classList.add("active");
            });
        });

        // Thêm vào giỏ hàng
        document.getElementById("ThemVaoGioHang").addEventListener("click", function () {
            let color = document.querySelector(".color-btn.active");
            let maMau = color.getAttribute("data-maMau");
            let maSP = "<?php echo $sanPham['MaSP']; ?>";
            let soLuong = 1;

            fetch("../Cart/add_to_cart.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `maSP=${encodeURIComponent(maSP)}&maMau=${encodeURIComponent(maMau)}&soLuong=${soLuong}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    cartCount.textContent = data.slMatHang; // Cập nhật số lượng mặt hàng
                } else {
                    alert(data.message);
                }
            })
        });

        // Mua ngay
        document.getElementById("MuaNgay").addEventListener("click", function () {
            let color = document.querySelector(".color-btn.active");
            let maMau = color.getAttribute("data-maMau");
            let maSP = "<?php echo $sanPham['MaSP']; ?>";
            let soLuong = 1;

            fetch("../Cart/add_to_cart.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `maSP=${encodeURIComponent(maSP)}&maMau=${encodeURIComponent(maMau)}&soLuong=${soLuong}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = "../Cart/cart.php"; // Chuyển hướng đến giỏ hàng
                } else {
                    alert(data.message);
                }
            })
        });
    });
    </script>
</body>
</html>
<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['AD', 'NV'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaSP từ URL
$maSP = isset($_GET['id']) ? trim($_GET['id']) : '';
if (empty($maSP)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Kiểm tra action
$action = isset($_GET['action']) ? trim($_GET['action']) : 'detail';

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
if ($result->num_rows > 0) {
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
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . htmlspecialchars($_SESSION['user']['HoTen']) : "";
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';

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
            <div class="user-greeting"><?php echo $nguoiDung; ?></div>
            <a href="../Account/profile.php?id=<?php echo $_SESSION['user']['MaNguoiDung']; ?>" class="icon">
                <i class="fas fa-user"></i> Hồ sơ
            </a>
            <a href="../../logout.php" class="icon">
                <i class="fas fa-sign-out"></i> Đăng xuất
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="container mt-4">
        <div class="card shadow-lg pb-4" style="max-width: 90%; margin: 0 auto;">
            <div class="card-header brand-header text-center mb-4">Chi tiết sản phẩm</div>
            <div class="row" style="padding-right: 30px">
                <!-- Hình ảnh sản phẩm -->
                <div class="col-md-6 text-center">
                    <div class="d-flex mt-5 justify-content-center">
                        <img id="product-image" src="../../Images/SanPham/<?php echo $chiTietSanPhams[0]['AnhBia']; ?>" 
                             alt="<?php echo htmlspecialchars($sanPham['TenSP']); ?>" class="img-fluid" style="width: 300px; height: 300px;">
                    </div>
                    <h3 class="mt-3" style="font-size: 20px">Chọn màu sắc:</h3>
                    <div class="color-options d-flex justify-content-center">
                        <?php foreach ($chiTietSanPhams as $item): ?>
                            <button class="btn btn-outline-dark m-1 color-btn" 
                                    data-maMau="<?php echo htmlspecialchars($item['MaMau']); ?>" 
                                    data-image="<?php echo htmlspecialchars($item['AnhBia']); ?>" 
                                    data-tenMau="<?php echo htmlspecialchars($item['TenMau']); ?>"
                                    style="width: 12%">
                                <?php echo htmlspecialchars($item['TenMau']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Thông tin sản phẩm -->
                <div class="col-md-6">
                    <h2 class="text-center"><?php echo htmlspecialchars($sanPham['TenSP']); ?></h2>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item" style="font-size: 18px"><strong>Mã sản phẩm:</strong> <?php echo htmlspecialchars($sanPham['MaSP']); ?></li>
                        <li class="list-group-item" style="font-size: 18px"><strong>Hãng:</strong> <?php echo htmlspecialchars($sanPham['TenHSX']); ?></li>
                        <li class="list-group-item" style="font-size: 18px"><strong>Giá bán:</strong> <span class="price" style="font-size: 18px"><?php echo number_format($sanPham['GiaBan'], 0, ',', '.'); ?> đ</span></li>
                        <li class="list-group-item" style="font-size: 18px"><strong>RAM:</strong> <?php echo htmlspecialchars($sanPham['Ram']); ?> GB</li>
                        <li class="list-group-item" style="font-size: 18px"><strong>Dung lượng:</strong> <?php echo htmlspecialchars($sanPham['DungLuong']); ?> GB</li>
                        <li class="list-group-item" style="font-size: 18px"><strong>Hệ điều hành:</strong> <?php echo htmlspecialchars($sanPham['HeDieuHanh']); ?></li>
                        <li class="list-group-item" style="font-size: 18px"><strong>Mô tả:</strong> <?php echo htmlspecialchars($sanPham['Mota'] ?: 'Không có mô tả.'); ?></li>
                    </ul>
                    <?php if ($vaiTro === 'AD'): ?>
                        <?php if ($action == "detail"): ?>
                            <div class="d-flex justify-content-center gap-2 mt-4 mb-3">
                                <a href="product_color_create.php?id=<?php echo $sanPham['MaSP']; ?>" class="btn2">Thêm màu</a>
                            </div>
                        <?php elseif ($action == "edit"): ?>
                            <div class="d-flex justify-content-center gap-2 mt-4 mb-3">
                                <button id="SuaAnh" class="btn1">Sửa ảnh</button>
                                <a href="product_edit.php?id=<?php echo $sanPham['MaSP']; ?>" class="btn2">Sửa thông tin</a>
                            </div>
                        <?php elseif ($action == "delete"): ?>
                            <div class="d-flex justify-content-center gap-2 mt-4 mb-3">
                                <button type="button" class="btn1 delete-color-btn" 
                                        style="border-radius: 5px; color: white;">
                                    Xóa màu
                                </button>
                                <button type="button" class="btn btn-danger delete-product-btn" 
                                        data-masp="<?php echo $maSP; ?>" style="border-radius: 5px; color: white;">
                                    Xóa sản phẩm
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <a href="product.php">Quay lại</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận xóa màu -->
    <div class="modal fade" id="deleteColorModal" tabindex="-1" role="dialog" aria-labelledby="deleteColorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-center" style="background: red; color: white;">
                    <div class="modal-title" id="deleteColorModalLabel" 
                         style="font-weight: bold; font-size: 20px; align-content: center;">
                        Xác nhận xóa màu
                    </div>
                </div>
                <div class="modal-body">
                    <div id="deleteColorMessage" class="alert alert-danger text-center" style="display: none;"></div>
                    <p class="text-center" style="color: red; font-weight: bold;">
                        Bạn có chắc chắn muốn xóa sản phẩm <strong id="modalTenSPMau"></strong> màu <strong id="modalTenMau"></strong> không?
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn2" data-dismiss="modal">Quay lại</button>
                        <button type="button" id="confirmDeleteColorBtn" class="btn btn-danger">Xóa</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận xóa sản phẩm -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-center" style="background: red; color: white;">
                    <div class="modal-title" id="deleteModalLabel" 
                         style="font-weight: bold; font-size: 20px; align-content: center;">
                        Xác nhận xóa sản phẩm
                    </div>
                </div>
                <div class="modal-body">
                    <div id="deleteMessage" class="alert alert-danger text-center" style="display: none;"></div>
                    <p class="text-center" style="color: red; font-weight: bold;">
                        Bạn có chắc chắn muốn xóa sản phẩm <strong id="modalTenSP"></strong> không?
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn2" data-dismiss="modal">Quay lại</button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Xóa</button>
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
            // Lấy tất cả các nút màu và hình ảnh sản phẩm
            let buttons = document.querySelectorAll(".color-btn");
            let image = document.getElementById("product-image");
            let selectedColor = null;

            // Chọn màu đầu tiên mặc định
            if (buttons.length > 0) {
                let defaultColor = buttons[0];
                let newImage = defaultColor.getAttribute("data-image");
                image.src = "../../Images/SanPham/" + newImage;
                defaultColor.classList.add("active");
                selectedColor = {
                    maMau: defaultColor.getAttribute("data-maMau"),
                    tenMau: defaultColor.getAttribute("data-tenMau")
                };
            }

            // Xử lý sự kiện khi chọn màu
            buttons.forEach(button => {
                button.addEventListener("click", function() {
                    let newImage = this.getAttribute("data-image");
                    image.src = "../../Images/SanPham/" + newImage;
                    buttons.forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");
                    selectedColor = {
                        maMau: this.getAttribute("data-maMau"),
                        tenMau: this.getAttribute("data-tenMau")
                    };
                });
            });

            // Sửa ảnh sản phẩm
            <?php if ($vaiTro === 'AD'): ?>
                $("#SuaAnh").click(function() {
                    let color = document.querySelector(".color-btn.active");
                    let maMau = color ? color.getAttribute("data-maMau") : buttons[0].getAttribute("data-maMau");
                    let maSP = "<?php echo htmlspecialchars($sanPham['MaSP']); ?>";
                    window.location.href = `product_image_edit.php?id=${encodeURIComponent(maSP)}&maMau=${encodeURIComponent(maMau)}`;
                });
            <?php endif; ?>

            // Xóa sản phẩm
            $('.delete-product-btn').click(function() {
                let maSP = $(this).data('masp');
                $('#modalTenSP').text('<?php echo htmlspecialchars($sanPham['TenSP']); ?>');
                $('#confirmDeleteBtn').data('maSP', maSP);
                $('#deleteMessage').hide().text('');
                $('#confirmDeleteBtn').prop('disabled', false);
                $('#deleteModal').modal('show');
            });

            // Xác nhận xóa sản phẩm
            $('#confirmDeleteBtn').click(function() {
                let maSP = $(this).data('maSP');
                $.ajax({
                    url: 'product_delete.php',
                    type: 'POST',
                    data: {
                        confirm_delete: true,
                        id: maSP
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            window.location.href = '../admin.php';
                        } else {
                            $('#deleteMessage').text(response.message).show();
                            $('#confirmDeleteBtn').prop('disabled', true);
                        }
                    },
                });
            });

            // Xóa màu
            $('.delete-color-btn').click(function() {
                if (!selectedColor) {
                    alert("Vui lòng chọn một màu để xóa!");
                    return;
                }
                let maSP = "<?php echo htmlspecialchars($sanPham['MaSP']); ?>";
                let maMau = selectedColor.maMau;
                let tenMau = selectedColor.tenMau;
                $('#modalTenSPMau').text('<?php echo htmlspecialchars($sanPham['TenSP']); ?>');
                $('#modalTenMau').text(tenMau);
                $('#confirmDeleteColorBtn').data('maSP', maSP).data('maMau', maMau);
                $('#deleteColorMessage').hide().text('');
                $('#confirmDeleteColorBtn').prop('disabled', false);
                $('#deleteColorModal').modal('show');
            });

            // Xác nhận xóa màu
            $('#confirmDeleteColorBtn').click(function() {
                let maSP = $(this).data('maSP');
                let maMau = $(this).data('maMau');
                $.ajax({
                    url: 'product_color_delete.php',
                    type: 'POST',
                    data: {
                        confirm_color_delete: true,
                        maSP: maSP,
                        maMau: maMau
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.redirect;
                        } else {
                            $('#deleteColorMessage').text(response.message).show();
                            $('#confirmDeleteColorBtn').prop('disabled', true);
                        }
                    },
                });
            });
        });
    </script>
</body>
</html>
<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['AD', 'NV'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy tham số tìm kiếm từ GET
$maNguoiDung = isset($_GET['MaNguoiDung']) ? $_GET['MaNguoiDung'] : '';
$hoTen = isset($_GET['HoTen']) ? $_GET['HoTen'] : '';
$sdt = isset($_GET['SDT']) ? $_GET['SDT'] : '';
$email = isset($_GET['Email']) ? $_GET['Email'] : '';
$gioiTinh = isset($_GET['GioiTinh']) && $_GET['GioiTinh'] !== '' ? (int)$_GET['GioiTinh'] : null;
$vaiTro = isset($_GET['VaiTro']) ? $_GET['VaiTro'] : '';

// Chuẩn bị dữ liệu cho giao diện
$viewData = [
    'MaNguoiDung' => $maNguoiDung,
    'HoTen' => $hoTen,
    'SDT' => $sdt,
    'Email' => $email,
    'GioiTinh' => $gioiTinh,
    'VaiTro' => $vaiTro
];

// Xây dựng truy vấn SQL
$sql = "SELECT MaNguoiDung, HoTen, GioiTinh, SDT, Email, AnhDaiDien, VaiTro 
        FROM NguoiDung 
        WHERE VaiTro != 'AD'";
$params = [];
$types = '';

if (!empty($maNguoiDung)) {
    $sql .= " AND MaNguoiDung LIKE ?";
    $params[] = "%$maNguoiDung%";
    $types .= 's';
}
if (!empty($hoTen)) {
    $sql .= " AND HoTen LIKE ?";
    $params[] = "%$hoTen%";
    $types .= 's';
}
if (!empty($sdt)) {
    $sql .= " AND SDT LIKE ?";
    $params[] = "%$sdt%";
    $types .= 's';
}
if (!empty($email)) {
    $sql .= " AND Email LIKE ?";
    $params[] = "%$email%";
    $types .= 's';
}
if ($gioiTinh !== null) {
    $sql .= " AND GioiTinh = ?";
    $params[] = $gioiTinh;
    $types .= 'i';
}
if (!empty($vaiTro)) {
    $sql .= " AND VaiTro = ?";
    $params[] = $vaiTro;
    $types .= 's';
}
$sql .= " ORDER BY MaNguoiDung";

// Phân trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = 10;
$offset = ($page - 1) * $rowsPerPage;

// Đếm tổng số người dùng
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

// Lấy dữ liệu người dùng theo phân trang
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
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

// Xử lý thông tin người dùng
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . htmlspecialchars($_SESSION['user']['HoTen']) : '';
$vaiTroUser = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa ảnh sản phẩm</title>
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
                        <a class="dropdown-item" href="account.php">
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

    <!-- Phần tìm kiếm người dùng -->
    <div class="container mt-4 mx-auto" style="width: 830px">
        <div class="card shadow-lg pb-4">
            <div class="card-header brand-header text-center">
                Tìm kiếm người dùng
            </div>
            <div class="card-body">
                <form id="search-form" method="GET" action="user.php">
                    <table class="table1" align="center" cellpadding="8" style="width: 100%;">
                        <tr>
                            <td><strong>Mã người dùng:</strong></td>
                            <td>
                                <input type="text" name="MaNguoiDung" class="form-control" style="width: 100%;" 
                                       value="<?php echo htmlspecialchars($viewData['MaNguoiDung']); ?>">
                            </td>
                            <td><strong>Họ tên:</strong></td>
                            <td>
                                <input type="text" name="HoTen" class="form-control" style="width: 100%;" 
                                       value="<?php echo htmlspecialchars($viewData['HoTen']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <td><strong>SĐT:</strong></td>
                            <td>
                                <input type="text" name="SDT" class="form-control" style="width: 100%;" 
                                       value="<?php echo htmlspecialchars($viewData['SDT']); ?>">
                            </td>
                            <td><strong>Email:</strong></td>
                            <td>
                                <input type="text" name="Email" class="form-control" style="width: 100%;" 
                                       value="<?php echo htmlspecialchars($viewData['Email']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Giới tính:</strong></td>
                            <td>
                                <input type="radio" name="GioiTinh" value="1" 
                                       <?php echo $viewData['GioiTinh'] === 1 ? 'checked' : ''; ?>> Nam&nbsp;
                                <input type="radio" name="GioiTinh" value="0" 
                                       <?php echo $viewData['GioiTinh'] === 0 ? 'checked' : ''; ?>> Nữ
                            </td>
                            <td><strong>Vai trò:</strong></td>
                            <td>
                                <input type="radio" name="VaiTro" value="NV"
                                       <?php echo $viewData['VaiTro'] === 'NV' ? 'checked' : ''; ?>> Nhân viên&nbsp;
                                <input type="radio" name="VaiTro" value="KH" 
                                       <?php echo $viewData['VaiTro'] === 'KH' ? 'checked' : ''; ?>> Khách hàng
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

    <!-- Phần danh sách người dùng -->
    <div class="container">
        <div class="card shadow-lg pb-4 mt-4" style="max-width: 100%; margin: 0 auto;" id="user-list">
            <div class="card-header brand-header text-center">
                Danh sách người dùng
            </div>
            <div class="card-body">
                <?php if ($vaiTroUser == "AD"): ?>
                    <div class="d-flex justify-content-end mb-3">
                        <a href="account_create.php" class="btn1">Thêm mới</a>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-center align-middle">
                        <thead style="font-weight: bold;">
                            <tr>
                                <th>STT</th>
                                <th>Mã người dùng</th>
                                <th>Họ tên</th>
                                <th>Giới tính</th>
                                <th>SĐT</th>
                                <th>Email</th>
                                <th>Ảnh đại diện</th>
                                <th>Vai trò</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = ($page - 1) * $rowsPerPage;
                            foreach ($users as $user):
                                $i++;
                                $gioiTinh = $user['GioiTinh'] == 1 ? 'Nam' : 'Nữ';
                                $vaiTroText = $user['VaiTro'] == 'KH' ? 'Khách hàng' : 'Nhân viên';
                            ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo htmlspecialchars($user['MaNguoiDung']); ?></td>
                                <td><?php echo htmlspecialchars($user['HoTen']); ?></td>
                                <td><?php echo $gioiTinh; ?></td>
                                <td><?php echo htmlspecialchars($user['SDT']); ?></td>
                                <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                <td>
                                    <img src="../../Images/User/<?php echo htmlspecialchars($user['AnhDaiDien'] ?: 'default.jpg'); ?>" 
                                         class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td><?php echo $vaiTroText; ?></td>
                                <td> 
                                    <a href="profile.php?id=<?php echo htmlspecialchars($user['MaNguoiDung']); ?>" 
                                       class="btn" style="background: #fd710d; color: white; border-radius: 5px; margin-right: 5px;">
                                        Xem
                                    </a>
                                    <?php if ($vaiTroUser == 'AD'): ?>
                                        <button type="button" class="btn btn-danger delete-user-btn" 
                                                data-manguoidung="<?php echo htmlspecialchars($user['MaNguoiDung']); ?>" 
                                                data-hoten="<?php echo htmlspecialchars($user['HoTen']); ?>" 
                                                style="border-radius: 5px; color: white;">
                                            Xóa
                                        </button>
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
                // Hàm tạo query string giữ tham số tìm kiếm
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
                <a href="../admin.php">Về trang chủ</a>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận xóa -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-center" style="background: red; color: white;">
                    <div class="modal-title" id="deleteModalLabel" 
                         style="font-weight: bold; font-size: 20px; align-content: center;">
                        Xác nhận xóa người dùng
                    </div>
                </div>
                <div class="modal-body">
                    <div id="deleteMessage" class="alert alert-danger text-center" style="display: none;"></div>
                    <p class="text-center" style="color: red; font-weight: bold;">
                        Bạn có chắc chắn muốn xóa người dùng <strong id="modalMaNguoiDung"></strong> không?
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
        // Tự cuộn đến danh sách khi tìm kiếm
        document.addEventListener("DOMContentLoaded", function () {
            if (window.location.search.length > 0) {
                setTimeout(() => {
                    document.getElementById("user-list").scrollIntoView({ behavior: "smooth" });
                }, 0);
            }
        });

        // Reset form
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("reset-button").addEventListener("click", function (event) {
                event.preventDefault();
                let form = document.getElementById("search-form");
                form.querySelectorAll("input").forEach(input => {
                    if (input.type === "text") {
                        input.value = "";
                    }
                    if (input.type === "radio") {
                        input.checked = false;
                    }
                });
                window.history.replaceState(null, null, window.location.pathname);
            });
        });

        // Xử lý xóa người dùng
        $(document).ready(function() {
            $('.delete-user-btn').click(function() {
                var maNguoiDung = $(this).data('manguoidung');
                $('#modalMaNguoiDung').text(maNguoiDung);
                $('#confirmDeleteBtn').data('manguoidung', maNguoiDung);
                $('#deleteMessage').hide().text('');
                $('#confirmDeleteBtn').prop('disabled', false);
                $('#deleteModal').modal('show');
            });

            $('#confirmDeleteBtn').click(function() {
                var maNguoiDung = $(this).data('manguoidung');
                $.ajax({
                    url: 'account_delete.php',
                    type: 'POST',
                    data: {
                        confirm_delete: true,
                        id: maNguoiDung
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            window.location.href = 'account.php';
                        } else {
                            $('#deleteMessage').text(response.message).show();
                            $('#confirmDeleteBtn').prop('disabled', true);
                        }
                    },
                });
            });
        });
    </script>
</body>
</html>
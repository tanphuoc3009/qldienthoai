<?php
require ('../../config.php');

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH') {
    header("HTTP/1.1 400 Bad Request");
    die(json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ!']));
}

// Lấy dữ liệu từ yêu cầu
$maSP = isset($_POST['maSP']) ? $_POST['maSP'] : '';
$maMau = isset($_POST['maMau']) ? $_POST['maMau'] : '';
$soLuong = isset($_POST['soLuong']) ? (int)$_POST['soLuong'] : 1;

// Kiểm tra dữ liệu đầu vào
if (empty($maSP) || empty($maMau)) {
    header("HTTP/1.1 400 Bad Request");
    die(json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ!']));
}

// Truy vấn thông tin sản phẩm
$sql = "SELECT ct.MaSP, ct.MaMau, ct.AnhBia, ms.TenMau, 
               sp.TenSP, sp.GiaBan
        FROM ChiTietSanPham ct
        JOIN SanPham sp ON ct.MaSP = sp.MaSP
        JOIN MauSac ms ON ct.MaMau = ms.MaMau
        WHERE ct.MaSP = ? AND ct.MaMau = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]));
}

$stmt->bind_param("ss", $maSP, $maMau);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("HTTP/1.1 404 Not Found");
    die(json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm!']));
}

$sanPham = $result->fetch_assoc();
$stmt->close();

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['GioHang']) || !is_array($_SESSION['GioHang'])) {
    $_SESSION['GioHang'] = [];
}

// Kiểm tra sản phẩm đã có trong giỏ hàng chưa
$gioHang = $_SESSION['GioHang'];
$check = false;
foreach ($gioHang as &$item) {
    if ($item['MaSP'] == $maSP && $item['MaMau'] == $maMau) {
        $item['SoLuong'] += $soLuong; // Nếu sản phẩm đã có, tăng số lượng
        $check = true;
        break;
    }
}

// Nếu sản phẩm chưa có, thêm mới
if (!$check) {
    $gioHang[] = [
        'MaSP' => $sanPham['MaSP'],
        'TenSP' => $sanPham['TenSP'],
        'MaMau' => $sanPham['MaMau'],
        'TenMau' => $sanPham['TenMau'],
        'GiaBan' => $sanPham['GiaBan'],
        'SoLuong' => $soLuong,
        'AnhBia' => $sanPham['AnhBia']
    ];
}

// Cập nhật session
$_SESSION['GioHang'] = $gioHang;
$_SESSION['SLMatHang'] = count($gioHang);

// Đóng kết nối
mysqli_close($conn);

// Trả về phản hồi JSON
echo json_encode(['success' => true, 'message' => 'Thêm vào giỏ hàng thành công!', 'slMatHang' => $_SESSION['SLMatHang']]);
?>
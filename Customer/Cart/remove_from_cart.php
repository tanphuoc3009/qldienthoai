<?php
require '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH') {
    header("HTTP/1.1 400 Bad Request");
    die(json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ!']));
}

$maSP = isset($_POST['maSP']) ? $_POST['maSP'] : '';
$maMau = isset($_POST['maMau']) ? $_POST['maMau'] : '';

if ($maSP && $maMau) {
    foreach ($_SESSION['GioHang'] as $key => $value) {
        if ($value['MaSP'] === $maSP && $value['MaMau'] === $maMau) {
            unset($_SESSION['GioHang'][$key]); // Xóa sản phẩm khỏi giỏ hàng
            break;
        }
    }
    // Cập nhật lại số lượng mặt hàng
    $_SESSION['SLMatHang'] = count($_SESSION['GioHang']);
    echo json_encode(['success' => true]);
    exit;
}

mysqli_close($conn);
?>
<?php
require '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH') {
    header("HTTP/1.1 400 Bad Request");
    die(json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ!']));
}

$maSP = isset($_POST['maSP']) ? $_POST['maSP'] : '';
$maMau = isset($_POST['maMau']) ? $_POST['maMau'] : '';
$soLuong = isset($_POST['soLuong']) ? (int)$_POST['soLuong'] : 0;

if ($maSP && $maMau && $soLuong >= 1) {
    foreach ($_SESSION['GioHang'] as &$item) {
        if ($item['MaSP'] === $maSP && $item['MaMau'] === $maMau) {
            $item['SoLuong'] = $soLuong;
            break;
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

mysqli_close($conn);
?>
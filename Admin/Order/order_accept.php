<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaDDH từ GET
$maDDH = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($maDDH)) {
    header("HTTP/1.1 400 Bad Request");
    die("Mã đơn hàng không hợp lệ!");
}

// Xử lý POST (xác nhận duyệt đơn)
if (isset($_POST['confirm_accept'])) {
    // Lấy mã nhân viên
    $maNV = isset($_POST['maNV']) ? $_POST['maNV'] : '';
    
    // Truy vấn đơn hàng
    $sql = "SELECT TinhTrang FROM DonDatHang WHERE MaDDH = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $maDDH);
    $stmt->execute();
    $result = $stmt->get_result();
    $donHang = $result->fetch_assoc();
    $stmt->close();

    // Kiểm tra TinhTrang
    if ($donHang['TinhTrang'] == 0) {
        // Cập nhật TinhTrang = 1 (Chờ giao hàng) và MaNhanVien
        $sql = "UPDATE DonDatHang SET TinhTrang = 1, MaNhanVien = ? WHERE MaDDH = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $maNV, $maDDH);
        $stmt->execute();
        $stmt->close();
        
        // Chuyển hướng về order.php
        header("Location: order.php");
        exit;
    }
}
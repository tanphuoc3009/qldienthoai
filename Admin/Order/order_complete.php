<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'NV') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaDDH từ GET
$maDDH = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($maDDH)) {
    header("HTTP/1.1 400 Bad Request");
    die("Mã đơn hàng không hợp lệ!");
}

// Xử lý POST (xác nhận hoàn thành)
if (isset($_POST['confirm_complete'])) {
    // Truy vấn đơn hàng
    $sql = "SELECT TinhTrang FROM DonDatHang WHERE MaDDH = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }
    $stmt->bind_param("s", $maDDH);
    $stmt->execute();
    $result = $stmt->get_result();
    $donHang = $result->fetch_assoc();
    $stmt->close();

    // Kiểm tra TinhTrang
    if ($donHang && $donHang['TinhTrang'] == 1) {
        // Cập nhật ThanhToan = 1 (Đã thanh toán) và TinhTrang = 2 (Thành công)
        $sql = "UPDATE DonDatHang SET ThanhToan = 1, TinhTrang = 2 WHERE MaDDH = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn: " . $conn->error);
        }
        $stmt->bind_param("s", $maDDH);
        $stmt->execute();
        $stmt->close();
    }

    // Chuyển hướng về order.php
    header("Location: order.php");
    exit();
}

mysqli_close($conn);
?>
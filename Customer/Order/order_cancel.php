<?php
require ('../../config.php');

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaDDH từ GET
$maDDH = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($maDDH)) {
    header("HTTP/1.1 400 Bad Request");
    die("Mã đơn hàng không hợp lệ!");
}

// Xử lý POST (xác nhận hủy)
if (isset($_POST['confirm_cancel'])) {
    // Truy vấn đơn hàng
    $sql = "SELECT MaKhachHang, TinhTrang FROM DonDatHang WHERE MaDDH = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }
    $stmt->bind_param("s", $maDDH);
    $stmt->execute();
    $result = $stmt->get_result();
    $donHang = $result->fetch_assoc();
    $stmt->close();

    // Kiểm tra đơn hàng tồn tại và quyền sở hữu
    if (!$donHang || $donHang['MaKhachHang'] !== $_SESSION['user']['MaNguoiDung']) {
        header("HTTP/1.1 404 Not Found");
        die("Yêu cầu không hợp lệ!");
    }

    // Kiểm tra TinhTrang
    if ($donHang['TinhTrang'] == 0) {
        // Cập nhật TinhTrang = 3 (Đã hủy)
        $sql = "UPDATE DonDatHang SET TinhTrang = 3 WHERE MaDDH = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Lỗi chuẩn bị truy vấn: " . $conn->error);
        }
        $stmt->bind_param("s", $maDDH);
        $stmt->execute();
        $stmt->close();
    }

    // Chuyển hướng về orders.php
    header("Location: order.php");
    exit();
}

mysqli_close($conn);
?>

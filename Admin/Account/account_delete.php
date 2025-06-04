<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaNguoiDung từ request
$maNguoiDung = isset($_POST['id']) ? $_POST['id'] : '';
if (empty($maNguoiDung)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy thông tin người dùng
$sql = "SELECT MaNguoiDung, HoTen, VaiTro FROM NguoiDung WHERE MaNguoiDung = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $maNguoiDung);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Lỗi thực thi truy vấn: ' . $stmt->error]);
    exit;
}
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
if (!$user) {
    header("HTTP/1.1 400 Bad Request");
    die("Không tìm thấy người dùng!");
}

// Xử lý xóa (POST với confirm_delete)
if (isset($_POST['confirm_delete'])) {
    // Kiểm tra vai trò admin
    if ($user['VaiTro'] == 'AD') {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản của admin!']);
        exit;
    }

    // Kiểm tra đơn hàng liên quan
    $sql = "SELECT COUNT(*) as count FROM DonDatHang WHERE MaKhachHang = ? OR MaNhanVien = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $maNguoiDung, $maNguoiDung);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasOrders = $result->fetch_assoc()['count'] > 0;
    $stmt->close();

    if ($hasOrders) {
        $message = $user['VaiTro'] == 'KH' 
            ? 'Không thể xóa khách hàng vì đang có đơn hàng liên quan!' 
            : 'Không thể xóa nhân viên vì đang có đơn hàng liên quan!';
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    // Xóa người dùng
    $sql = "DELETE FROM NguoiDung WHERE MaNguoiDung = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $maNguoiDung);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa người dùng: ' . $stmt->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Xóa thành công!']);
    exit;
}

mysqli_close($conn);
?>

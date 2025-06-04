<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ!']);
    exit;
}

// Lấy MaMau từ request
$maMau = isset($_POST['id']) ? trim($_POST['id']) : '';
if (empty($maMau)) {
    echo json_encode(['success' => false, 'message' => 'Mã màu không hợp lệ!']);
    exit;
}

// Lấy thông tin màu sắc
$sql = "SELECT MaMau, TenMau FROM MauSac WHERE MaMau = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $maMau);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi thực thi truy vấn: ' . $stmt->error]);
    exit;
}
$result = $stmt->get_result();
$mauSac = $result->fetch_assoc();
$stmt->close();

if (!$mauSac) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy màu sắc!']);
    exit;
}

// Xử lý xóa (POST)
if (isset($_POST['confirm_delete'])) {
    // Kiểm tra xem màu có được sử dụng trong ChiTietSanPham không
    $sql = "SELECT COUNT(*) as count FROM ChiTietSanPham WHERE MaMau = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $maMau);
    $stmt->execute();
    $result = $stmt->get_result();
    $existsInProduct = $result->fetch_assoc()['count'] > 0;
    $stmt->close();

    if ($existsInProduct) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa màu này vì đang được sử dụng trong sản phẩm!']);
        exit;
    }

    // Xóa màu sắc
    $sql = "DELETE FROM MauSac WHERE MaMau = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $maMau);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa màu sắc: ' . $stmt->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Xóa thành công!']);
    exit;
}

mysqli_close($conn);
?>
<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaSP từ request
$id = isset($_POST['id']) ? $_POST['id'] : '';
if (empty($id)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Kiểm tra sản phẩm tồn tại
$sql = "SELECT MaSP FROM SanPham WHERE MaSP = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Lỗi thực thi truy vấn: ' . $stmt->error]);
    exit;
}
$result = $stmt->get_result();
$sp = $result->fetch_assoc();
if (!$sp) {
    header("HTTP/1.1 404 Not Found");
    die("Không tìm thấy sản phẩm!");
}
$stmt->close();

// Xử lý xóa (POST)
if (isset($_POST['confirm_delete'])) {
    // Kiểm tra xem sản phẩm có trong đơn đặt hàng không
    $sql = "SELECT COUNT(*) as count FROM ChiTietDDH WHERE MaSP = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existsInOrder = $result->fetch_assoc()['count'] > 0;
    $stmt->close();

    if ($existsInOrder) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa sản phẩm này vì đang có trong đơn hàng!']);
        exit;
    }

    // Xóa tất cả ChiTietSanPham
    $sql = "DELETE FROM ChiTietSanPham WHERE MaSP = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa chi tiết sản phẩm: ' . $stmt->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Xóa SanPham
    $sql = "DELETE FROM SanPham WHERE MaSP = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa sản phẩm: ' . $stmt->error]);
        $stmt->close();
        exit;
    } 
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Xóa thành công!']);
    exit;
}

mysqli_close($conn);
?>
<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaSP và MaMau từ request
$maSP = isset($_POST['maSP']) ? $_POST['maSP'] : '';
$maMau = isset($_POST['maMau']) ? $_POST['maMau'] : '';
if (empty($maSP) || empty($maMau)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Kiểm tra ChiTietSanPham tồn tại
$sql = "SELECT MaSP, MaMau FROM ChiTietSanPham WHERE MaSP = ? AND MaMau = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
    exit;
}
$stmt->bind_param("ss", $maSP, $maMau);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Lỗi thực thi truy vấn: ' . $stmt->error]);
    exit;
}
$result = $stmt->get_result();
$sp = $result->fetch_assoc();
if (!$sp) {
    header("HTTP/1.1 404 Not Found");
    die("Không tìm thấy sản phẩm với màu này!");
}
$stmt->close();

// Xử lý xóa (POST)
if (isset($_POST['confirm_color_delete'])) {
    // Kiểm tra xem sản phẩm có trong đơn đặt hàng không
    $sql = "SELECT COUNT(*) as count FROM ChiTietDDH WHERE MaSP = ? AND MaMau = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $maSP, $maMau);
    $stmt->execute();
    $result = $stmt->get_result();
    $existsInOrder = $result->fetch_assoc()['count'] > 0;
    $stmt->close();

    if ($existsInOrder) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa sản phẩm này vì đang có trong đơn hàng!']);
        exit;
    }

    // Xóa màu sản phẩm trong ChiTietSanPham
    $sql = "DELETE FROM ChiTietSanPham WHERE MaSP = ? AND MaMau = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $maSP, $maMau);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa màu sản phẩm: ' . $stmt->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Kiểm tra xem sản phẩm còn bản ghi trong ChiTietSanPham không
    $sql = "SELECT COUNT(*) as count FROM ChiTietSanPham WHERE MaSP = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $maSP);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();

    if ($count == 0) {
        // Xóa SanPham nếu không còn màu
        $sql = "DELETE FROM SanPham WHERE MaSP = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $maSP);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa sản phẩm: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Xóa thành công!', 'redirect' => '../admin.php']);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Xóa thành công!', 'redirect' => 'product_details.php?id=' . $maSP . '&action=delete']);
    exit;
}

mysqli_close($conn);
?>
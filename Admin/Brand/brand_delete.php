<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'AD') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy MaHSX từ request
$maHSX = isset($_POST['id']) ? $_POST['id'] : '';
if (empty($maHSX)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy thông tin hãng sản xuất
$sql = "SELECT MaHSX, TenHSX FROM HangSanXuat WHERE MaHSX = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $maHSX);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Lỗi thực thi truy vấn: ' . $stmt->error]);
    exit;
}
$result = $stmt->get_result();
$hsx = $result->fetch_assoc();
$stmt->close();
if (!$hsx) {
    header("HTTP/1.1 404 Not Found");
    die("Không tìm thấy hãng sản xuất!");
}

// Xử lý xóa (POST)
if (isset($_POST['confirm_delete'])) {
    // Kiểm tra xem có sản phẩm nào thuộc hãng không
    $sql = "SELECT COUNT(*) as count FROM SanPham WHERE MaHSX = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $maHSX);
    $stmt->execute();
    $result = $stmt->get_result();
    $existsInProduct = $result->fetch_assoc()['count'] > 0;
    $stmt->close();

    if ($existsInProduct) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa hãng sản xuất này vì đang có sản phẩm thuộc hãng!']);
        exit;
    } else {
        // Xóa hãng sản xuất
        $sql = "DELETE FROM HangSanXuat WHERE MaHSX = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $maHSX);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa hãng sản xuất: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Xóa thành công!']);
        exit;
    }
}

mysqli_close($conn);
?>
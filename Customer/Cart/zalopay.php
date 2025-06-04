<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Lấy thông tin người dùng và giỏ hàng
$gioHang = $_SESSION['GioHang'];
$nguoiDung = $_SESSION['user'];
$orderId = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($orderId)) {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Cấu hình ZaloPay
$appId = $ZaloPayAppId;
$key1 = $ZaloPayKey1;
$createOrderUrl = $ZaloPayCreateOrderUrl;

// Tính tổng tiền
$amount = 0;
foreach ($gioHang as $item) {
    $amount += $item['GiaBan'] * $item['SoLuong'];
}

// Thông tin đơn hàng
$appTransId = date("ymd") . "_" . $orderId;
$appTime = round(microtime(true) * 1000); // miliseconds
$appUser = $nguoiDung['MaNguoiDung'];
$description = "$orderId - {$nguoiDung['MaNguoiDung']} - {$nguoiDung['HoTen']}";
$embedData = [
    'redirecturl' => "http://" . $_SERVER['HTTP_HOST'] . "/QLDienThoai/Customer/Cart/zalopay_result.php?appTransId=" . urlencode($appTransId),
    'method' => ''
];
$items = [];
foreach ($gioHang as $item) {
    $items[] = [
        'itemid' => $item['MaSP'],
        'itemname' => $item['TenSP'],
        'itemprice' => (int)$item['GiaBan'],
        'itemquantity' => $item['SoLuong']
    ];
}

// Tạo dữ liệu gửi lên ZaloPay
$order = [
    'appid' => $appId,
    'appuser' => $appUser,
    'apptime' => $appTime,
    'amount' => $amount,
    'apptransid' => $appTransId,
    'embeddata' => json_encode($embedData, JSON_UNESCAPED_UNICODE),
    'item' => json_encode($items, JSON_UNESCAPED_UNICODE),
    'description' => $description,
    'bankcode' => null
];

// Tạo chữ ký (mac)
$data = "{$appId}|{$order['apptransid']}|{$order['appuser']}|{$order['amount']}|{$order['apptime']}|{$order['embeddata']}|{$order['item']}";
$order['mac'] = hash_hmac('sha256', $data, $key1);

// Gửi yêu cầu đến ZaloPay
$ch = curl_init($createOrderUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($order));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$orderUrl = $result['orderurl'];

// Kiểm tra kết nối đến ZaloPay
if (empty($orderUrl)) {
    header("Location: zalopay_result.php?appTransId=" . urlencode($appTransId));
    exit;
}

header("Location: $orderUrl");
exit;
?>
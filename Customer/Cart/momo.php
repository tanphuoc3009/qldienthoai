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

// Hàm tạo chữ ký MoMo
function createSignatureMoMo($rawSignature, $secretKey) {
    return hash_hmac('sha256', $rawSignature, $secretKey);
}

// Cấu hình MoMo
$endpoint = $MoMoEndpoint;
$partnerCode = $MoMoPartnerCode;
$accessKey = $MoMoAccessKey;
$secretKey = $MoMoSecretKey;

// Tính tổng tiền
$amount = 0;
foreach ($gioHang as $item) {
    $amount += $item['GiaBan'] * $item['SoLuong'];
}

// Thông tin đơn hàng
$requestId = $orderId;
$orderInfo = "$orderId - {$nguoiDung['MaNguoiDung']} - {$nguoiDung['HoTen']}";
$returnUrl = "http://" . $_SERVER['HTTP_HOST'] . "/QLDienThoai/Customer/Cart/momo_result.php";
$notifyUrl = $returnUrl;
$requestType = "payWithMethod";
$extraData = "";

// Tạo chữ ký
$rawSignature = "accessKey=$accessKey&amount=$amount&extraData=$extraData&ipnUrl=$notifyUrl&orderId=$orderId&orderInfo=$orderInfo&partnerCode=$partnerCode&redirectUrl=$returnUrl&requestId=$requestId&requestType=$requestType";
$signature = createSignatureMoMo($rawSignature, $secretKey);

// Dữ liệu gửi lên MoMo
$request = [
    'partnerCode' => $partnerCode,
    'accessKey' => $accessKey,
    'requestId' => $requestId,
    'amount' => $amount,
    'orderId' => $orderId,
    'orderInfo' => $orderInfo,
    'redirectUrl' => $returnUrl,
    'ipnUrl' => $notifyUrl,
    'extraData' => $extraData,
    'requestType' => $requestType,
    'signature' => $signature,
    'lang' => 'vi',
    'partnerName' => 'MoMo Payment',
    'storeId' => 'Test Store',
    'autoCapture' => true
];

// Gửi yêu cầu đến MoMo
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);
$payUrl = isset($json['payUrl']) ? $json['payUrl'] : '';

// Kiểm tra kết nối đến Momo
if (empty($payUrl)) {
    $code = isset($json['code']) ? $json['code'] : '-1';
    $message = isset($json['message']) ? $json['message'] : 'Không thể kết nối với MoMo.';
    header("Location: momo_result.php?resultCode=$code&orderId=$orderId&message=" . urlencode($message));
    exit;
}

header("Location: $payUrl");
exit;
?>
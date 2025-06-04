<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qldienthoai";

$conn = mysqli_connect($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối database thất bại: " . mysqli_connect_error());
}

// Bắt đầu phiên
session_start();

// Mật khẩu ứng dụng
$app_password = '';

// Cấu hình Momo
$MoMoPartnerCode= "MOMO";
$MoMoAccessKey = "F8BBA842ECF85";
$MoMoSecretKey = "K951B6PE1waDMi640xX08PD3vg6EkVlz";
$MoMoEndpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

// Cấu hình ZaloPay
$ZaloPayAppId = "554";
$ZaloPayKey1 = "8NdU5pG5R2spGHGhyO99HN1OhD8IQJBn";
$ZaloPayKey2 = "uUfsWgfLkRLzq6W2uNXTCxrfxs51auny";
$ZaloPayCreateOrderUrl = "https://sandbox.zalopay.com.vn/v001/tpe/createorder";
$ZaloPayQueryOrderUrl = "https://sandbox.zalopay.com.vn/v001/tpe/getstatusbyapptransid";
?>
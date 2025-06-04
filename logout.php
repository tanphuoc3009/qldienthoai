<?php
require 'config.php';

// Xóa tất cả dữ liệu phiên
$_SESSION['user'] = null;
$_SESSION['SLMatHang'] = null;
$_SESSION['GioHang'] = null;

// Hủy phiên
session_destroy();

// Chuyển hướng đến trang chủ
header("Location: index.php");
exit;
?>
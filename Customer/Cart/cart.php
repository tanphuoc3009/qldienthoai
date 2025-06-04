<?php
require '../../config.php';

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'KH') {
    header("HTTP/1.1 400 Bad Request");
    die("Yêu cầu không hợp lệ!");
}

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['GioHang'])) {
    $_SESSION['GioHang'] = [];
}
if (!isset($_SESSION['SLMatHang'])) {
    $_SESSION['SLMatHang'] = 0;
}

// Lấy giỏ hàng và thông tin người dùng
$gioHang = $_SESSION['GioHang'];
$nguoiDung = isset($_SESSION['user']) ? "Xin chào, " . htmlspecialchars($_SESSION['user']['HoTen']) : "";
$slMatHang = $_SESSION['SLMatHang'];

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng</title>
    <link rel="stylesheet" href="../../Content/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../../Content/Style.css">
</head>
<body>
    <!-- Header -->
    <div id="header">
        <a href="../../index.php">
            <div class="logo">
                <img src="../../Images/Banner/logo.jpg" alt="Logo">
            </div>
        </a>
        <div class="nav-icons" style="margin-right: 90px">
            <div class="user-greeting"><?php echo $nguoiDung; ?></div>
            <a href="cart.php" class="icon">
                <i class="fas fa-shopping-cart"></i> Giỏ hàng(<span id="SLMatHang"><?php echo $slMatHang; ?></span>)
            </a>
            <a href="../Order/order.php" class="icon">
                <i class="fas fa-receipt"></i> Đơn hàng
            </a>
            <a href="../Account/profile.php?id=<?php echo $_SESSION['user']['MaNguoiDung']; ?>" class="icon">
                <i class="fas fa-user"></i> Hồ sơ
            </a>
            <a href="../../logout.php" class="icon">
                <i class="fas fa-sign-out"></i> Đăng xuất
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="container">
        <div class="card shadow-lg pb-4 mt-4" style="max-width: 90%; margin: 0 auto;">
            <div class="card-header brand-header text-center">
                Giỏ hàng của bạn
            </div>
            <div class="card-body">
                <?php if (empty($gioHang)): ?>
                    <div class="text-center mt-4">
                        <h4 class="mb-4">Giỏ hàng của bạn đang trống!</h4>
                        <a href="../../index.php" class="btn" style="background-color: #ff6b6b; color: white; border-radius: 5px;">Mua sắm ngay</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered text-center align-middle">
                            <thead style="font-weight: bold">
                                <tr>
                                    <th>STT</th>
                                    <th>Sản phẩm</th>
                                    <th>Hình ảnh</th>
                                    <th>Màu sắc</th>
                                    <th>Số lượng</th>
                                    <th>Giá bán</th>
                                    <th>Thành tiền</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $index = 0; $tongTien = 0; ?>
                                <?php foreach ($gioHang as $item): ?>
                                    <tr>
                                        <td><?php echo ++$index; ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['TenSP']); ?></strong></td>
                                        <td>
                                            <img src="../../Images/SanPham/<?php echo htmlspecialchars($item['AnhBia']); ?>" 
                                                 alt="Ảnh sản phẩm" class="img-thumbnail" style="width: 80px; height: 80px; border: none">
                                        </td>
                                        <td><?php echo htmlspecialchars($item['TenMau']); ?></td>
                                        <td>
                                            <div class="d-flex justify-content-center align-items-center">
                                                <a class="btn fw-bold" style="font-size: 24px; padding: 10px;" 
                                                   onclick="updateQuantity('<?php echo $item['MaSP']; ?>', '<?php echo $item['MaMau']; ?>', -1)">-</a>
                                                <input type="text" class="form-control d-inline" 
                                                       style="width: 50px; height: 50px; text-align: center;" 
                                                       value="<?php echo $item['SoLuong']; ?>" readonly 
                                                       id="quantity-<?php echo $item['MaSP']; ?>-<?php echo $item['MaMau']; ?>">
                                                <a class="btn fw-bold" style="font-size: 24px; padding: 10px;" 
                                                   onclick="updateQuantity('<?php echo $item['MaSP']; ?>', '<?php echo $item['MaMau']; ?>', 1)">+</a>
                                            </div>
                                        </td>
                                        <td class="text-end"><?php echo number_format($item['GiaBan'], 0, ',', '.'); ?> đ</td>
                                        <td class="text-end fw-bold" id="thanhTien-<?php echo $item['MaSP']; ?>-<?php echo $item['MaMau']; ?>">
                                            <?php 
                                            $thanhTien = $item['GiaBan'] * $item['SoLuong'];
                                            $tongTien += $thanhTien;
                                            echo number_format($thanhTien, 0, ',', '.'); 
                                            ?> đ
                                        </td>
                                        <td>
                                            <button class="btn btn-danger" 
                                                    onclick="removeFromCart('<?php echo $item['MaSP']; ?>', '<?php echo $item['MaMau']; ?>')" 
                                                    style="width: 70px; border-radius: 5px;">Xóa</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="fw-bold text-success">
                                    <td colspan="6" class="text-end" style="color: red; font-weight: bold; font-size: 18px">Tổng Tiền:</td>
                                    <td class="text-end" style="color: red; font-weight: bold; font-size: 18px" id="totalPrice">
                                        <?php echo number_format($tongTien, 0, ',', '.'); ?> đ
                                    </td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center d-flex justify-content-center gap-2">
                        <a href="../../index.php" class="btn1">Tiếp tục mua sắm</a>
                        <a href="checkout.php" class="btn2">Đặt hàng</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer">
        Copyright © <?php echo date('Y'); ?> - Ego Mobile
    </div>

    <script>
        // Hàm cập nhật số lượng sản phẩm trong giỏ hàng
        function updateQuantity(maSP, maMau, change) { 
            const input = document.getElementById(`quantity-${maSP}-${maMau}`); // Lấy input có id dựa trên mã sản phẩm và mã màu
            let currentQuantity = parseInt(input.value);
            let newQuantity = currentQuantity + change; // Tính toán lại số lượng

            if (newQuantity >= 1) {
                input.value = newQuantity; // Cập nhật giá trị trong input
                
                // Cập nhật giỏ hàng
                fetch('update_cart.php', {
                    method: 'POST',
                    body: `maSP=${encodeURIComponent(maSP)}&maMau=${encodeURIComponent(maMau)}&soLuong=${newQuantity}`,
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                        // Cập nhật lại thành tiền sau khi thay đổi số lượng
                          const priceText = document.querySelector(`#thanhTien-${maSP}-${maMau}`).previousElementSibling.innerText; // Lấy thành tiền
                          const price = parseFloat(priceText.replace(/[^\d]/g, ''));
                          const newPrice = newQuantity * price; // Tính lại thành tiền
                          
                          // Cập nhật lại thành tiền
                          document.getElementById(`thanhTien-${maSP}-${maMau}`).innerText = formatCurrency(newPrice);

                          // Cập nhật tổng tiền giỏ hàng
                          updateTotalPrice();
                      }
                  });
            }
        }

        // Hàm cập nhật tổng tiền giỏ hàng
        function updateTotalPrice() {
            let totalPrice = 0;
            const rows = document.getElementsByTagName('tr'); // Lấy tất cả các phần tử <tr> trong trang
            for (let i = 1; i < rows.length; i++) {
                const priceCell = rows[i].querySelector('td:nth-child(7)'); // Lấy cột thứ 7 (Thành tiền)

                // Tính toán lại tổng tiền
                const price = parseFloat(priceCell.innerText.replace(/[^\d]/g, ''));
                totalPrice += price;
            }

            // Cập nhật tổng tiền giỏ hàng sau khi tính toán xong
            document.getElementById('totalPrice').innerText = formatCurrency(totalPrice);
        }

        function formatCurrency(value) {
            return value.toLocaleString('vi-VN') + ' đ';
        }

        // Xóa sản phẩm khỏi giỏ hàng
        function removeFromCart(maSP, maMau) {
            fetch('remove_from_cart.php', {
                method: 'POST',
                body: `maSP=${encodeURIComponent(maSP)}&maMau=${encodeURIComponent(maMau)}`,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(response => response.json())
              .then(data => {
                  if (data.success) location.reload();
              });
        }
    </script>
</body>
</html>
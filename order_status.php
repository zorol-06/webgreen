<?php
include 'components/connection.php';
session_start();

// 🔹 Kiểm tra đăng nhập
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: login.php");
    exit;
}

// 🔹 Đăng xuất
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<style type="text/css">
  <?php include 'style.css'; ?>
</style>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Coffee - Đơn hàng của tôi</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
  <?php include 'components/header.php'; ?>

  <div class="main">
    <div class="banner">
      <h1>Đơn hàng của tôi</h1>
    </div>

    <div class="title2">
      <a href="home.php">Trang chủ</a><span> / Đơn hàng của tôi</span>
    </div>
<section class="orders-container">
    <div class="box-container">
        <?php
        // Truy vấn đơn hàng của user hiện tại
        $select_orders = $conn->prepare("
            SELECT o.*, p.name as product_name, p.image 
            FROM orders o 
            JOIN products p ON o.product_id = p.id 
            WHERE o.user_id = ? 
            ORDER BY o.date DESC
        ");
        $select_orders->execute([$user_id]);
        
        if ($select_orders->rowCount() > 0) {
            while ($order = $select_orders->fetch(PDO::FETCH_ASSOC)) {
        ?>
        <div class="order-box">
            <div class="order-header">
                <p><strong>Mã đơn hàng:</strong> #<?= $order['id'] ?></p>
                <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['date'])) ?></p>
                <span class="status <?= $order['status'] ?>"><?= $order['status'] ?></span>
            </div>
            
            <div class="order-body">
                <div class="product-info">
                    <!-- HIỂN THỊ ẢNH SẢN PHẨM (Lấy từ code form sản phẩm) -->
                    <img src="img/<?= htmlspecialchars($order['image']); ?>" class="img">
                    
                    <div class="product-details">
                        <h3 class="name"><?= htmlspecialchars($order['product_name']); ?></h3>
                        <div class="flex">
                            <p class="price">Giá: <?= number_format($order['price'], 0, ',', '.'); ?> VND</p>
                            <p class="qty-display">Số lượng: <?= $order['qty']; ?></p>
                        </div>
                        <p class="subtotal">Thành tiền: <?= number_format($order['price'] * $order['qty'], 0, ',', '.'); ?> VND</p>
                    </div>
                </div>
                
                <div class="order-details">
                    <p><strong>Phương thức thanh toán:</strong> <?= $order['method'] ?></p>
                    <p><strong>Trạng thái thanh toán:</strong> 
                        <span class="payment-status <?= $order['payment_status'] ?>">
                            <?= $order['payment_status'] ?>
                        </span>
                    </p>
                    <p><strong>Địa chỉ giao hàng:</strong> <?= $order['address'] ?></p>
                    <p><strong>Loại địa chỉ:</strong> <?= $order['address_type'] ?></p>
                    
                    <?php if ($order['coupon_code']): ?>
                    <p><strong>Mã giảm giá:</strong> <?= $order['coupon_code'] ?></p>
                    <p><strong>Giảm giá:</strong> -<?= number_format($order['discount_amount'], 0, ',', '.') ?> VNĐ</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="order-footer">
                <p><strong>Tổng đơn hàng:</strong> 
                    <span class="total-price">
                        <?= number_format($order['final_price'], 0, ',', '.') ?> VND
                    </span>
                </p>
                
                <?php if ($order['status'] == 'pending'): ?>
                <form method="post" class="cancel-form">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" name="cancel_order" class="btn" onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">Hủy đơn hàng</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
            }
        } else {
            echo '<p class="empty">Bạn chưa có đơn hàng nào!</p>';
        }
        ?>
    </div>
</section>
    

    <?php include 'components/footer.php'; ?>
  </div>

  <script src="script.js"></script>
</body>
</html>
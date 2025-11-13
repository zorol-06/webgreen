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

    <section class="orders">
      <div class="box-container">
        <?php
        // 🔹 Lấy tất cả đơn hàng của người dùng hiện tại
        $select_orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
        $select_orders->execute([$user_id]);

        if ($select_orders->rowCount() > 0) {
            while ($fetch_order = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                // 🔹 Lấy thông tin sản phẩm tương ứng - Sửa logic: Fallback nếu product không tồn tại
                $select_product = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
                $select_product->execute([$fetch_order['product_id']]);
                $fetch_product = $select_product->fetch(PDO::FETCH_ASSOC);
                
                if (!$fetch_product) {
                    $fetch_product = ['name' => 'Sản phẩm không xác định', 'image' => 'default.jpg']; // Fallback dựa trên DB
                }
        ?>
        <div class="box">
          <img src="img/<?= $fetch_product['image']; ?>" alt="">
          <h3><?= $fetch_product['name']; ?></h3>
          <p>Số lượng: <span><?= $fetch_order['qty']; ?></span></p>
          <p>Giá: <span>$<?= number_format($fetch_order['price']); ?></span></p> <!-- 🔹 Sửa: Format price để dễ đọc (ví dụ: 1000 → 1,000) -->
          <p>Ngày đặt: <span><?= date('d/m/Y H:i', strtotime($fetch_order['date'])); ?></span></p> <!-- 🔹 Sửa: Format date readable (từ DB datetime raw) -->
          <p>Trạng thái: 
            <span style="color:<?php 
              if ($fetch_order['status'] == 'delivered') echo 'green';
              elseif ($fetch_order['status'] == 'canceled') echo 'red';
              else echo 'orange';
            ?>">
              <?php 
                if ($fetch_order['status'] == 'delivered') echo 'Đã giao hàng';
                elseif ($fetch_order['status'] == 'canceled') echo 'Đã hủy';
                else echo 'Đang xử lý';
              ?>
            </span>
          </p>

          <a href="order.php?get_id=<?= $fetch_order['id']; ?>" class="btn">Xem chi tiết</a>
        </div>
        <?php
            }
        } else {
            echo '<p class="empty">Bạn chưa có đơn hàng nào.</p>';
        }
        ?>
      </div>
    </section>

    <?php include 'components/footer.php'; ?>
  </div>

  <script src="script.js"></script>
</body>
</html>
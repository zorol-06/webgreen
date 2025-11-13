<?php
include 'components/connection.php';
session_start();

// 🔹 Kiểm tra đăng nhập người dùng
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

// 🔹 Xử lý đăng xuất
if (isset($_POST['logout'])) {
    session_destroy();
    header("location: login.php");
    exit;
}

// 🧩 Thêm sản phẩm vào danh sách yêu thích (Wishlist)
if (isset($_POST['add_to_wishlist'])) {
    if ($user_id == '') {
        header("location: login.php");
        exit;
    }

    $product_id = $_POST['product_id'];

    $verify_wishlist = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
    $verify_wishlist->execute([$user_id, $product_id]);

    $verify_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $verify_cart->execute([$user_id, $product_id]);

    if ($verify_wishlist->rowCount() > 0) {
        $warning_msg[] = 'Sản phẩm đã có trong danh sách yêu thích!';
    } elseif ($verify_cart->rowCount() > 0) {
        $warning_msg[] = 'Sản phẩm đã có trong giỏ hàng!';
    } else {
        // 🔹 Sửa: Kiểm tra sản phẩm tồn tại trước khi lấy giá
        $select_price = $conn->prepare("SELECT price FROM products WHERE id = ? LIMIT 1");
        $select_price->execute([$product_id]);
        if ($select_price->rowCount() > 0) {
            $fetch_price = $select_price->fetch(PDO::FETCH_ASSOC);

            $insert_wishlist = $conn->prepare("INSERT INTO wishlist (user_id, product_id, price) VALUES (?, ?, ?)");
            $insert_wishlist->execute([$user_id, $product_id, $fetch_price['price']]);
            $success_msg[] = 'Đã thêm sản phẩm vào danh sách yêu thích!';
        } else {
            $warning_msg[] = 'Sản phẩm không tồn tại!';
        }
    }
}

// 🧩 Thêm sản phẩm vào Giỏ hàng
if (isset($_POST['add_to_cart'])) {
    if ($user_id == '') {
        header("location: login.php");
        exit;
    }

    $product_id = $_POST['product_id'];
    $qty = isset($_POST['qty']) && $_POST['qty'] > 0 ? $_POST['qty'] : 1;
    $qty = filter_var($qty, FILTER_SANITIZE_NUMBER_INT);

    $verify_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $verify_cart->execute([$user_id, $product_id]);

    $max_cart_items = $conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $max_cart_items->execute([$user_id]);
    $count_items = $max_cart_items->fetchColumn();

    if ($verify_cart->rowCount() > 0) {
        $warning_msg[] = 'Sản phẩm đã có trong giỏ hàng!';
    } elseif ($count_items >= 20) {
        $warning_msg[] = 'Giỏ hàng của bạn đã đầy (tối đa 20 sản phẩm)!';
    } else {
        // 🔹 Sửa: Kiểm tra sản phẩm tồn tại trước khi lấy giá
        $select_price = $conn->prepare("SELECT price FROM products WHERE id = ? LIMIT 1");
        $select_price->execute([$product_id]);
        if ($select_price->rowCount() > 0) {
            $fetch_price = $select_price->fetch(PDO::FETCH_ASSOC);

            $insert_cart = $conn->prepare("INSERT INTO cart (user_id, product_id, price, qty) VALUES (?, ?, ?, ?)");
            $insert_cart->execute([$user_id, $product_id, $fetch_price['price'], $qty]);
            $success_msg[] = 'Đã thêm sản phẩm vào giỏ hàng!';
        } else {
            $warning_msg[] = 'Sản phẩm không tồn tại!';
        }
    }
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
  <title>Green Coffee - Chi tiết sản phẩm</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
  <?php include 'components/header.php'; ?>

  <div class="main">
    <div class="banner">
      <h1>Chi tiết sản phẩm</h1>
    </div>

    <div class="title2">
      <a href="home.php">Trang chủ</a><span> / Chi tiết sản phẩm</span>
    </div>

    <section class="view_page">
      <?php
      if (isset($_GET['pid'])) {
          $pid = $_GET['pid'];
          $select_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
          $select_product->execute([$pid]);

          if ($select_product->rowCount() > 0) {
              $fetch_product = $select_product->fetch(PDO::FETCH_ASSOC);
      ?>
      <form method="post">
        <img src="img/<?= htmlspecialchars($fetch_product['image']); ?>" alt="<?= htmlspecialchars($fetch_product['name']); ?>">
        <div class="detail">
          <div class="price">Giá: $<?= number_format($fetch_product['price']); ?></div> <!-- 🔹 Sửa: Format giá dễ đọc -->
          <div class="name"><?= htmlspecialchars($fetch_product['name']); ?></div>
          <div class="desc">
            <p><?= htmlspecialchars($fetch_product['product_detail']); ?></p>
          </div>

          <input type="hidden" name="product_id" value="<?= $fetch_product['id']; ?>">

          <div class="button">
            <button type="submit" name="add_to_wishlist" class="btn">
              Thêm vào yêu thích <i class="bx bx-heart"></i>
            </button>

            <input type="hidden" name="qty" value="1" class="quantity">

            <button type="submit" name="add_to_cart" class="btn">
              Thêm vào giỏ hàng <i class="bx bx-cart"></i>
            </button>
          </div>
        </div>
      </form>
      <?php
          } else {
              echo "<p style='text-align:center;margin:20px;'>❌ Không tìm thấy sản phẩm!</p>";
          }
      } else {
          echo "<p style='text-align:center;margin:20px;'>❌ Thiếu tham số sản phẩm!</p>";
      }
      ?>
    </section>

    <?php include 'components/footer.php'; ?>
  </div>

  <!-- Scripts -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="script.js"></script>
  <?php include 'components/alert.php'; ?>
</body>
</html>
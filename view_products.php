<?php
include 'components/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("location: login.php");
    exit;
}

// 🧩 Thêm sản phẩm vào Wishlist
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
        $warning_msg[] = 'Product already exists in your wishlist';
    } elseif ($verify_cart->rowCount() > 0) {
        $warning_msg[] = 'Product already exists in your cart';
    } else {
        $select_price = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $select_price->execute([$product_id]);
        $fetch_price = $select_price->fetch(PDO::FETCH_ASSOC);

        $insert_wishlist = $conn->prepare("INSERT INTO wishlist (user_id, product_id, price) VALUES (?, ?, ?)");
        $insert_wishlist->execute([$user_id, $product_id, $fetch_price['price']]);
        $success_msg[] = 'Product added to wishlist successfully';
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
        $warning_msg[] = 'Product already exists in your cart';
    } elseif ($count_items >= 20) {
        $warning_msg[] = 'Your cart is full (maximum 20 items)';
    } else {
        $select_price = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $select_price->execute([$product_id]);
        $fetch_price = $select_price->fetch(PDO::FETCH_ASSOC);

        $insert_cart = $conn->prepare("INSERT INTO cart (user_id, product_id, price, qty) VALUES (?, ?, ?, ?)");
        $insert_cart->execute([$user_id, $product_id, $fetch_price['price'], $qty]);
        $success_msg[] = 'Product added to cart successfully';
    }
}
?>
<style type="text/css">
  <?php include 'style.css'; ?>
</style>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Coffee - Shop Page</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
  <?php include 'components/header.php'; ?>

  <div class="main">
    <div class="banner">
      <h1>Shop</h1>
    </div>

    <div class="title2">
      <a href="home.php">Home</a><span> / Our Shop</span>
    </div>

    <section class="products">
      <div class="box-container">
        <?php
        $select_products = $conn->prepare("SELECT * FROM products");
        $select_products->execute();
        if ($select_products->rowCount() > 0) {
          while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
        ?>
        <form action="" method="post" class="box">
          <img src="img/<?= $fetch_products['image'] ?>" class="img">

          <div class="button">
            <button type="submit" name="add_to_cart"><i class="bx bx-cart"></i></button>
            <button type="submit" name="add_to_wishlist"><i class="bx bx-heart"></i></button>
            <a href="view_page.php?pid=<?= $fetch_products['id']; ?>" class="bx bxs-show"></a>
          </div>

          <h3 class="name"><?= htmlspecialchars($fetch_products['name']); ?></h3>
          <input type="hidden" name="product_id" value="<?= $fetch_products['id']; ?>">

          <div class="flex">
            <p class="price">Price $<?= $fetch_products['price']; ?>/-</p>
            <input type="number" name="qty" min="1" max="99" value="1" class="qty">
          </div>

          <a href="checkout.php?get_id=<?= $fetch_products['id']; ?>" class="btn">Buy Now</a>
        </form>
        <?php
          }
        } else {
          echo '<p class="empty">No products added yet!</p>';
        }
        ?>
      </div>
    </section>

    <?php include 'components/footer.php'; ?>
  </div>

  <!-- Scripts -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="script.js"></script>
  <?php include 'components/alert.php'; ?>
</body>
</html>

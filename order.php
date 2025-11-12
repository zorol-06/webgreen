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
?>
<style type="text/css">
  <?php include 'style.css'; ?>
</style>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Coffee - Order Page</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
  <?php include 'components/header.php'; ?>

  <div class="main">
    <div class="banner">
      <h1>Shop</h1>
    </div>

    <div class="title2">
      <a href="home.php">Home</a><span> / My Orders</span>
    </div>

    <section class="orders">
      <div class="box-container">
        <div class="title">
          <img src="img/download.png" alt="" class="logo">
          <h1>My Orders</h1>
          <p>Lorem ipsum dolor sit amet consectetur adipisicing elit...</p>
        </div>

        <div class="box-container">
          <?php
          $select_orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY date DESC");
          $select_orders->execute([$user_id]);

          if ($select_orders->rowCount() > 0) {
              while ($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                  $select_products = $conn->prepare("SELECT * FROM products WHERE id = ?");
                  $select_products->execute([$fetch_orders['product_id']]);

                  if ($select_products->rowCount() > 0) {
                      while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
          ?>
          <div class="box" <?php if($fetch_orders['status']=="canceled"){echo 'style="border:2px solid red;"';}?>>
            <a href="view_order.php?get_id=<?= $fetch_orders['id']; ?>">
              <p class="date"><i class="bi bi-calendar-fill"></i><span><?= $fetch_orders['date']; ?></span></p>
              <img src="img/<?= $fetch_products['image']; ?>" class="img" alt="">
              <div class="row">
                <h3 class="name"><?= $fetch_products['name']; ?></h3>
                <p class="price">Price: $<?= $fetch_orders['price']; ?> x <?= $fetch_orders['qty']; ?></p>
                <p class="status" style="color:<?php 
                    if($fetch_orders['status']=='delivered'){
                        echo 'green';
                    } elseif($fetch_orders['status']=='canceled'){
                        echo 'red';
                    } else {
                        echo 'orange';
                    } ?>">
                  <?= ucfirst($fetch_orders['status']); ?>
                </p>
              </div>
            </a>
          </div>
          <?php
                      }
                  }
              }
          } else {
              echo '<p class="empty">No orders have been placed yet!</p>';
          }
          ?>
        </div>
      </div>
    </section>

    <?php include 'components/footer.php'; ?>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="script.js"></script>
  <?php include 'components/alert.php'; ?>
</body>
</html>

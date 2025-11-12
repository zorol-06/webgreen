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
        $select_price = $conn->prepare("SELECT price FROM products WHERE id = ? LIMIT 1");
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
        $select_price = $conn->prepare("SELECT price FROM products WHERE id = ? LIMIT 1");
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
    <title>Green Coffee - Product Detail</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'components/header.php'; ?>
    <div class="main">
        <div class="banner">
            <h1>Product Detail</h1>
        </div>
        <div class="title2">
            <a href="home.php">Home</a><span> / Product Detail</span>
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
                            <div class="price">$<?= $fetch_product['price']; ?></div>
                            <div class="name"><?= htmlspecialchars($fetch_product['name']); ?></div>
                            <div class="detail">
                                <p><?= htmlspecialchars($fetch_product['description'] ?? 'No description available.'); ?></p>
                            </div>
                            <input type="hidden" name="product_id" value="<?= $fetch_product['id']; ?>">
                            <div class="button">
                                <button type="submit" name="add_to_wishlist" class="btn">
                                    Add to wishlist <i class="bx bx-heart"></i>
                                </button>
                                <input type="hidden" name="qty" value="1" class="quantity">
                                <button type="submit" name="add_to_cart" class="btn">
                                    Add to cart <i class="bx bx-cart"></i>
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

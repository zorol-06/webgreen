<?php
include 'components/connection.php';
session_start();

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

// 🧩 Cập nhật số lượng sản phẩm trong giỏ hàng
if (isset($_POST['update_cart'])) {
    $cart_id = filter_var($_POST['cart_id'], FILTER_SANITIZE_STRING);
    $qty = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);

    if ($qty > 0 && $qty <= 99) { // 🔹 Giới hạn qty hợp lý
        $update_qty = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ? AND user_id = ?");
        $update_qty->execute([$qty, $cart_id, $user_id]);
        $success_msg[] = 'Đã cập nhật số lượng giỏ hàng thành công';
    } else {
        $warning_msg[] = 'Số lượng phải từ 1 đến 99!';
    }
}

// 🧩 Xóa item khỏi giỏ hàng
if (isset($_POST['delete_item'])) {
    $cart_id = $_POST['cart_id'] ?? null;
    $cart_id = filter_var($cart_id, FILTER_SANITIZE_STRING);

    if ($cart_id) {
        $verify_delete = $conn->prepare("SELECT * FROM cart WHERE id = ? AND user_id = ?");
        $verify_delete->execute([$cart_id, $user_id]);

        if ($verify_delete->rowCount() > 0) {
            $delete_cart_id = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $delete_cart_id->execute([$cart_id, $user_id]);
            $success_msg[] = 'Đã xóa sản phẩm khỏi giỏ hàng thành công';
        } else {
            $warning_msg[] = 'Sản phẩm đã được xóa khỏi giỏ hàng';
        }
    }
}

// 🧩 Xóa toàn bộ giỏ hàng
if (isset($_POST['empty_cart'])) {
    $verify_empty_item = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
    $verify_empty_item->execute([$user_id]);

    if ($verify_empty_item->rowCount() > 0) {
        $delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $delete_cart->execute([$user_id]);
        $success_msg[] = 'Đã xóa toàn bộ giỏ hàng thành công';
    } else {
        $warning_msg[] = 'Giỏ hàng đã trống';
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
    <title>Green Coffee - Giỏ Hàng</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
<?php include 'components/header.php'; ?>

<div class="main">
    <div class="banner">
        <h1>Giỏ Hàng Của Tôi</h1>
    </div>
    <div class="title2">
        <a href="home.php">Trang Chủ</a><span> / Giỏ Hàng</span>
    </div>

    <section class="products">
        <h1 class="title">Sản phẩm đã thêm vào giỏ hàng</h1>
        <div class="box-container">
            <?php
            $grand_total = 0;
            $select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
            $select_cart->execute([$user_id]);

            if ($select_cart->rowCount() > 0) {
                while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                    $select_products = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
                    $select_products->execute([$fetch_cart['product_id']]);
                    
                    if ($select_products->rowCount() > 0) {
                        $fetch_products = $select_products->fetch(PDO::FETCH_ASSOC);
                        $sub_total = $fetch_cart['qty'] * $fetch_products['price']; // 🔹 Tính trước khi echo
                        ?>
                        <form method="post" action="" class="box">
                            <input type="hidden" name="cart_id" value="<?= htmlspecialchars($fetch_cart['id']); ?>">
                            <img src="img/<?= htmlspecialchars($fetch_products['image']); ?>" class="img" alt="<?= htmlspecialchars($fetch_products['name']); ?>">
                            <h3 class="name"><?= htmlspecialchars($fetch_products['name']); ?></h3>
                            <div class="flex">
                                <p class="price">Giá $<?= number_format($fetch_products['price']); ?>/-</p>
                                <input type="number" name="qty" required min="1" max="99" value="<?= $fetch_cart['qty']; ?>" class="qty">
                                <button type="submit" name="update_cart" class="bx bxs-edit fa-edit" title="Cập nhật"></button>
                            </div>
                            <p class="sub-total">Tổng phụ: <span>$<?= number_format($sub_total); ?></span></p>
                            <button type="submit" name="delete_item" class="btn" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">Xóa</button>
                        </form>
                        <?php
                        $grand_total += $sub_total;
                    }
                }
            } else {
                echo '<p class="empty">Chưa có sản phẩm nào!</p>';
            }
            ?>
        </div>

        <?php if ($grand_total > 0) { ?>
        <div class="cart-total">
            <p>Tổng số tiền phải thanh toán: <span>$<?= number_format($grand_total); ?>/-</span></p>
            <div class="button">
                <form method="post">
                    <button type="submit" name="empty_cart" class="btn" onclick="return confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')">Xóa Giỏ Hàng</button>
                </form>
                <a href="checkout.php" class="btn">Tiến hành thanh toán</a>
            </div>
        </div>
        <?php } ?>
    </section>

    <?php include 'components/footer.php'; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src="script.js"></script>
<?php include 'components/alert.php'; ?>
</body>
</html>
<?php
include 'components/connection.php';
// 🔹 Kiểm tra session đã active chưa trước khi start (tránh duplicate error)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// 🔹 Đếm số lượng wishlist (chỉ sản phẩm active)
$count_wishlist_items = $conn->prepare("SELECT w.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ? AND p.status = 'active'");
$count_wishlist_items->execute([$user_id]);
$total_wishlist_items = $count_wishlist_items->rowCount();

// 🔹 Đếm số lượng cart (chỉ sản phẩm active)
$count_cart_items = $conn->prepare("SELECT c.* FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND p.status = 'active'");
$count_cart_items->execute([$user_id]);
$total_cart_items = $count_cart_items->rowCount();
?>
<header class="header">
    <div class="flex">
        <a href="home.php" class="logo"><img src="img/logo.jpg" alt="Logo Green Coffee"></a>
        <nav class="navbar">
            <a href="home.php">Trang Chủ</a>
            <a href="view_products.php">Sản Phẩm</a>
            <a href="order.php">Đơn Hàng</a>
            <a href="about.php">Về Chúng Tôi</a>
            <a href="contact.php">Liên Hệ</a>
        </nav>
        <div class="icons">
            <i class="bx bxs-user" id="user-btn" title="Tài Khoản"></i>
            <a href="wishlist.php" class="cart-btn" title="Danh Sách Yêu Thích"><i class="bx bx-heart"></i><sup><?= $total_wishlist_items; ?></sup></a>
            <a href="cart.php" class="cart-btn" title="Giỏ Hàng"><i class="bx bx-cart-download"></i><sup><?= $total_cart_items; ?></sup></a>
            <i class="bx bx-list-plus" id="menu-btn" style="font-size: 2rem ;" title="Menu"></i>
        </div>
       <div class="user-box">
    <?php if (isset($_SESSION['user_id'])): ?>
        <p>Tên Người Dùng: <span><?= htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span></p>
        <p>Email: <span><?= htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span></p>
        <form method="post">
            <button type="submit" name="logout" class="logout-btn">Đăng Xuất</button>
        </form>
    <?php else: ?>
        <a href="login.php" class="btn">Đăng Nhập</a>
        <a href="register.php" class="btn">Đăng Ký</a>
    <?php endif; ?>
</div>

    </div>
</header>
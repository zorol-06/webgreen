<?php
include 'components/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

// Xử lý đăng xuất
if (isset($_POST['logout'])) {
    session_destroy();
    header("location: login.php");
    exit;
}

// Cập nhật số lượng
if (isset($_POST['update_cart'])) {
    $cart_id = filter_var($_POST['cart_id'], FILTER_SANITIZE_STRING);
    $qty = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);

    if ($qty > 0 && $qty <= 99) {
        $update_qty = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ? AND user_id = ?");
        $update_qty->execute([$qty, $cart_id, $user_id]);
        $success_msg[] = 'Đã cập nhật số lượng giỏ hàng thành công';
    } else {
        $warning_msg[] = 'Số lượng phải từ 1 đến 99!';
    }
}

// Xóa item
if (isset($_POST['delete_item'])) {
    $cart_id = filter_var($_POST['cart_id'] ?? null, FILTER_SANITIZE_STRING);
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

// Xóa toàn bộ giỏ hàng
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

// Áp dụng mã giảm giá
if (isset($_POST['apply_coupon'])) {
    $coupon_code = trim($_POST['coupon_code']);
    $current_date = date('Y-m-d H:i:s');
    $verify_coupon = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND start_date <= ? AND expire_date >= ?");
    $verify_coupon->execute([$coupon_code, $current_date, $current_date]);
    if ($verify_coupon->rowCount() > 0) {
        $coupon = $verify_coupon->fetch(PDO::FETCH_ASSOC);
        if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
            $warning_msg[] = 'Mã giảm giá đã hết lượt sử dụng!';
        } else {
            $_SESSION['coupon'] = [
                'id' => $coupon['id'],
                'code' => $coupon['code'],
                'discount_type' => $coupon['discount_type'],
                'discount_value' => $coupon['discount_value'],
                'min_order' => $coupon['min_order'],
                'max_discount' => $coupon['max_discount']
            ];
            $success_msg[] = 'Áp dụng mã giảm giá thành công!';
        }
    } else {
        $warning_msg[] = 'Mã giảm giá không hợp lệ hoặc đã hết hạn!';
    }
}

// Xóa mã giảm giá
if (isset($_POST['remove_coupon'])) {
    unset($_SESSION['coupon']);
    $success_msg[] = 'Đã xóa mã giảm giá!';
}

// Hàm định dạng tiền VND (1.234.567 ₫)
function format_vnd($number) {
    return number_format($number, 0, ',', '.') . ' VND';
}

// Tính tổng tiền
$grand_total = 0;
$discount = 0;
$final_total = 0;

$select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
$select_cart->execute([$user_id]);

if ($select_cart->rowCount() > 0) {
    while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
        $select_products = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $select_products->execute([$fetch_cart['product_id']]);
        if ($select_products->rowCount() > 0) {
            $fetch_products = $select_products->fetch(PDO::FETCH_ASSOC);
            $sub_total = $fetch_cart['qty'] * $fetch_products['price'];
            $grand_total += $sub_total;
        }
    }
}

// Tính giảm giá
if (isset($_SESSION['coupon']) && $grand_total > 0) {
    $coupon = $_SESSION['coupon'];
    if ($grand_total >= $coupon['min_order']) {
        if ($coupon['discount_type'] == 'percent') {
            $discount = ($grand_total * $coupon['discount_value']) / 100;
            if ($coupon['max_discount'] !== null && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        } else {
            $discount = $coupon['discount_value'];
        }
        if ($discount > $grand_total) $discount = $grand_total;
    } else {
        $warning_msg[] = 'Đơn hàng chưa đủ điều kiện áp dụng mã giảm giá!';
        unset($_SESSION['coupon']);
    }
}

$final_total = $grand_total - $discount;

// Danh sách mã giảm giá hợp lệ
$current_date = date('Y-m-d H:i:s');
$select_valid_coupons = $conn->prepare("SELECT * FROM coupons WHERE status = 'active' AND start_date <= ? AND expire_date >= ? AND (usage_limit IS NULL OR used_count < usage_limit) ORDER BY discount_value DESC");
$select_valid_coupons->execute([$current_date, $current_date]);
$valid_coupons = $select_valid_coupons->fetchAll(PDO::FETCH_ASSOC);
?>

<style type="text/css">
<?php include 'style.css'; ?>
/* Giữ nguyên hoàn toàn CSS cũ của bạn - không thêm, không sửa gì cả */
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
            $select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
            $select_cart->execute([$user_id]);

            if ($select_cart->rowCount() > 0) {
                while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                    $select_products = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
                    $select_products->execute([$fetch_cart['product_id']]);
                    if ($select_products->rowCount() > 0) {
                        $fetch_products = $select_products->fetch(PDO::FETCH_ASSOC);
                        $sub_total = $fetch_cart['qty'] * $fetch_products['price'];
                        ?>
                        <form method="post" action="" class="box">
                            <input type="hidden" name="cart_id" value="<?= htmlspecialchars($fetch_cart['id']); ?>">
                            <img src="img/<?= htmlspecialchars($fetch_products['image']); ?>" class="img" alt="<?= htmlspecialchars($fetch_products['name']); ?>">
                            <h3 class="name"><?= htmlspecialchars($fetch_products['name']); ?></h3>
                            <div class="flex">
                                <p class="price"><?= format_vnd($fetch_products['price']); ?></p>
                                <input type="number" name="qty" required min="1" max="99" value="<?= $fetch_cart['qty']; ?>" class="qty">
                                <button type="submit" name="update_cart" class="bx bxs-edit fa-edit" title="Cập nhật"></button>
                            </div>
                            <p class="sub-total">Tổng phụ: <span><?= format_vnd($sub_total); ?></span></p>
                            <button type="submit" name="delete_item" class="btn" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">Xóa</button>
                        </form>
                        <?php
                    }
                }
            } else {
                echo '<p class="empty">Chưa có sản phẩm nào!</p>';
            }
            ?>
        </div>

        <?php if ($grand_total > 0) { ?>
        <div class="cart-total">
            <div class="coupon-section">
                <?php if (!isset($_SESSION['coupon'])) { ?>
                    <form method="post" class="coupon-form">
                        <select name="coupon_code" required class="coupon-select">
                            <option value="">-- Chọn mã giảm giá --</option>
                            <?php foreach ($valid_coupons as $coupon): 
                                $text = $coupon['discount_type'] == 'percent' 
                                    ? $coupon['discount_value'] . '%' 
                                    : format_vnd($coupon['discount_value']);
                                $min = $coupon['min_order'] > 0 ? ' (Tối thiểu ' . format_vnd($coupon['min_order']) . ')' : '';
                            ?>
                                <option value="<?= htmlspecialchars($coupon['code']); ?>">
                                    <?= htmlspecialchars($coupon['code']); ?> - Giảm <?= $text . $min; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="apply_coupon" class="btn">Áp dụng mã</button>
                    </form>
                <?php } else { ?>
                    <div class="applied-coupon">
                        <p>
                            Mã giảm giá: <strong><?= $_SESSION['coupon']['code']; ?></strong> 
                            (<?= $_SESSION['coupon']['discount_type'] == 'percent' 
                                ? $_SESSION['coupon']['discount_value'] . '%' 
                                : format_vnd($_SESSION['coupon']['discount_value']); ?>)
                        </p>
                        <form method="post" class="remove-coupon">
                            <button type="submit" name="remove_coupon" class="btn">Xóa mã</button>
                        </form>
                    </div>
                <?php } ?>
            </div>

            <div class="total-breakdown">
                <p>Tổng tiền hàng: <span><?= format_vnd($grand_total); ?></span></p>
                
                <?php if ($discount > 0) { ?>
                    <p class="discount">
                        Giảm giá (<?= $_SESSION['coupon']['code'] ?? ''; ?>): 
                        <span>-<?= format_vnd($discount); ?></span>
                    </p>
                <?php } ?>
                
                <p class="final-total">Tổng thanh toán: <span><?= format_vnd($final_total); ?></span></p>
            </div>

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
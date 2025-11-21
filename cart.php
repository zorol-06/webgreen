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

    if ($qty > 0 && $qty <= 99) {
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

// 🧩 Áp dụng mã giảm giá
if (isset($_POST['apply_coupon'])) {
    $coupon_code = filter_var($_POST['coupon_code'], FILTER_SANITIZE_STRING);
    
    // Kiểm tra mã giảm giá trong database
    $current_date = date('Y-m-d H:i:s');
    $verify_coupon = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND start_date <= ? AND expire_date >= ?");
    $verify_coupon->execute([$coupon_code, $current_date, $current_date]);
    
    if ($verify_coupon->rowCount() > 0) {
        $coupon = $verify_coupon->fetch(PDO::FETCH_ASSOC);
        
        // Kiểm tra giới hạn sử dụng
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

// 🧩 Xóa mã giảm giá
if (isset($_POST['remove_coupon'])) {
    unset($_SESSION['coupon']);
    $success_msg[] = 'Đã xóa mã giảm giá!';
}

// Tính tổng tiền và giảm giá
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

// Tính toán giảm giá nếu có mã
if (isset($_SESSION['coupon']) && $grand_total > 0) {
    $coupon = $_SESSION['coupon'];
    
    // Kiểm tra điều kiện đơn hàng tối thiểu
    if ($grand_total >= $coupon['min_order']) {
        if ($coupon['discount_type'] == 'percent') {
            $discount = ($grand_total * $coupon['discount_value']) / 100;
            
            // Áp dụng giới hạn giảm giá tối đa nếu có
            if ($coupon['max_discount'] !== null && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        } else {
            $discount = $coupon['discount_value'];
        }
        
        // Đảm bảo giảm giá không vượt quá tổng tiền
        if ($discount > $grand_total) {
            $discount = $grand_total;
        }
    } else {
        $warning_msg[] = 'Mã giảm giá yêu cầu đơn hàng tối thiểu $' . number_format($coupon['min_order']) . '. Vui lòng mua thêm sản phẩm!';
        unset($_SESSION['coupon']);
    }
}

$final_total = $grand_total - $discount;

// Lấy danh sách mã giảm giá hợp lệ
$current_date = date('Y-m-d H:i:s');
$select_valid_coupons = $conn->prepare("SELECT * FROM coupons WHERE status = 'active' AND start_date <= ? AND expire_date >= ? AND (usage_limit IS NULL OR used_count < usage_limit) ORDER BY discount_value DESC");
$select_valid_coupons->execute([$current_date, $current_date]);
$valid_coupons = $select_valid_coupons->fetchAll(PDO::FETCH_ASSOC);
?>
<style type="text/css">
<?php include 'style.css'; ?>
/* Thêm CSS cho phần giỏ hàng với dropdown */
.coupon-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.coupon-form {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
}

.coupon-select {
    flex: 1;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    background-color: white;
    cursor: pointer;
    transition: border-color 0.3s;
}

.coupon-select:focus {
    outline: none;
    border-color: #28a745;
    box-shadow: 0 0 5px rgba(40, 167, 69, 0.3);
}

.coupon-form .btn {
    padding: 12px 25px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
    font-weight: 500;
}

.coupon-form .btn:hover {
    background: #218838;
    transform: translateY(-1px);
}

.coupon-info {
    font-size: 13px;
    color: #666;
    margin-top: 10px;
    text-align: center;
}

.coupon-info strong {
    color: #333;
}

.applied-coupon {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 5px;
    margin-bottom: 10px;
}

.applied-coupon p {
    margin: 0;
    color: #155724;
    font-weight: 500;
    font-size: 14px;
}

.remove-coupon .btn {
    padding: 8px 15px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.3s;
}

.remove-coupon .btn:hover {
    background: #c82333;
}

.total-breakdown {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.total-breakdown p {
    display: flex;
    justify-content: space-between;
    margin: 0.8rem 0;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
    font-size: 15px;
}

.total-breakdown .discount {
    color: #dc3545;
    font-weight: 500;
}

.total-breakdown .final-total {
    font-size: 1.3rem;
    font-weight: bold;
    color: #28a745;
    border-top: 2px solid #28a745;
    margin-top: 1rem !important;
    padding-top: 1rem !important;
}

.cart-total .button {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.cart-total .button .btn {
    padding: 12px 25px;
    text-decoration: none;
    border-radius: 5px;
    transition: all 0.3s;
    font-weight: 500;
    text-align: center;
    min-width: 150px;
}

.cart-total .button .btn:first-child {
    background: #6c757d;
    color: white;
    border: none;
}

.cart-total .button .btn:first-child:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.cart-total .button .btn:last-child {
    background: #28a745;
    color: white;
}

.cart-total .button .btn:last-child:hover {
    background: #218838;
    transform: translateY(-1px);
}

/* Hiệu ứng cho dropdown */
.coupon-select option {
    padding: 10px;
}

.coupon-select option:first-child {
    color: #6c757d;
    font-style: italic;
}

/* Badge cho mã giảm giá */
.coupon-badge {
    display: inline-block;
    padding: 2px 6px;
    background: #28a745;
    color: white;
    border-radius: 3px;
    font-size: 10px;
    margin-left: 5px;
    vertical-align: middle;
}

/* Responsive */
@media (max-width: 768px) {
    .coupon-form {
        flex-direction: column;
    }
    
    .coupon-select {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .applied-coupon {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .cart-total .button {
        flex-direction: column;
    }
    
    .cart-total .button .btn {
        width: 100%;
    }
}

/* Hiển thị sản phẩm trong giỏ hàng */
.products .box-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.products .box {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 1.5rem;
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
    overflow: hidden;
}

.products .box:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.products .box .img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.products .box .name {
    font-size: 1.2rem;
    color: #333;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.products .box .flex {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 10px;
}

.products .box .price {
    font-size: 1.1rem;
    color: #28a745;
    font-weight: bold;
}

.products .box .qty {
    width: 60px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
}

.products .box .sub-total {
    font-size: 1rem;
    color: #666;
    margin-bottom: 1rem;
}

.products .box .sub-total span {
    color: #333;
    font-weight: bold;
}

.products .box .btn {
    width: 100%;
    padding: 10px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
}

.products .box .btn:hover {
    background: #c82333;
}

.products .box .fa-edit {
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 5px 10px;
    cursor: pointer;
    transition: background 0.3s;
}

.products .box .fa-edit:hover {
    background: #0069d9;
}

.empty {
    text-align: center;
    font-size: 1.2rem;
    color: #6c757d;
    padding: 2rem;
}
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
                                <p class="price">Giá $<?= number_format($fetch_products['price']); ?></p>
                                <input type="number" name="qty" required min="1" max="99" value="<?= $fetch_cart['qty']; ?>" class="qty">
                                <button type="submit" name="update_cart" class="bx bxs-edit fa-edit" title="Cập nhật"></button>
                            </div>
                            <p class="sub-total">Tổng phụ: <span>$<?= number_format($sub_total); ?></span></p>
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
            <!-- Phần mã giảm giá -->
            <div class="coupon-section">
                <?php if (!isset($_SESSION['coupon'])) { ?>
                    <form method="post" class="coupon-form">
                        <select name="coupon_code" required class="coupon-select">
                            <option value="">-- Chọn mã giảm giá --</option>
                            <?php
                            if (count($valid_coupons) > 0) {
                                foreach ($valid_coupons as $coupon) {
                                    $discount_text = $coupon['discount_type'] == 'percent' 
                                        ? $coupon['discount_value'] . '%' 
                                        : '$' . number_format($coupon['discount_value']);
                                    
                                    $min_order_text = $coupon['min_order'] > 0 
                                        ? ' (Đơn tối thiểu: $' . number_format($coupon['min_order']) . ')' 
                                        : '';
                                        
                                    $max_discount_text = $coupon['max_discount'] > 0 && $coupon['discount_type'] == 'percent'
                                        ? ' (Tối đa: $' . number_format($coupon['max_discount']) . ')'
                                        : '';
                                    
                                    echo '<option value="' . htmlspecialchars($coupon['code']) . '">' . 
                                         htmlspecialchars($coupon['code']) . ' - Giảm ' . $discount_text . 
                                         $min_order_text . $max_discount_text . '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>-- Không có mã giảm giá khả dụng --</option>';
                            }
                            ?>
                        </select>
                        <button type="submit" name="apply_coupon" class="btn">Áp dụng mã</button>
                    </form>
                    <?php if (count($valid_coupons) > 0) { ?>
                        <div class="coupon-info">
                            <strong>Chọn mã giảm giá phù hợp với đơn hàng của bạn</strong>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="applied-coupon">
                        <p>
                            ✅ Mã giảm giá: <strong><?= $_SESSION['coupon']['code']; ?></strong> 
                            (<?= $_SESSION['coupon']['discount_type'] == 'percent' ? 
                            $_SESSION['coupon']['discount_value'] . '%' : 
                            '$' . $_SESSION['coupon']['discount_value']; ?>)
                        </p>
                        <form method="post" class="remove-coupon">
                            <button type="submit" name="remove_coupon" class="btn">Xóa mã</button>
                        </form>
                    </div>
                <?php } ?>
            </div>

            <!-- Phần tổng kết tiền -->
            <div class="total-breakdown">
                <p>Tổng tiền hàng: <span>$<?= number_format($grand_total, 2); ?></span></p>
                
                <?php if ($discount > 0) { ?>
                    <p class="discount">
                        Giảm giá (<?= $_SESSION['coupon']['code']; ?>): 
                        <span>-$<?= number_format($discount, 2); ?></span>
                    </p>
                <?php } ?>
                
                <p class="final-total">Tổng thanh toán: <span>$<?= number_format($final_total, 2); ?></span></p>
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
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

// 🔹 Xử lý áp dụng mã giảm giá trong trang thanh toán
if (isset($_POST['apply_coupon_checkout'])) {
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

// 🔹 Xóa mã giảm giá trong trang thanh toán
if (isset($_POST['remove_coupon_checkout'])) {
    unset($_SESSION['coupon']);
    $success_msg[] = 'Đã xóa mã giảm giá!';
}

// 🔹 Xử lý đặt hàng khi người dùng bấm Place Order
if (isset($_POST['place_order'])) {
    if (empty($user_id)) {
        $warning_msg[] = 'Vui lòng đăng nhập để đặt hàng';
    } else {
        // 🔹 Lấy và lọc dữ liệu từ form với validation
        $name = trim(filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING));
        $number = trim(filter_var($_POST['number'] ?? '', FILTER_SANITIZE_STRING));
        $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $method = trim(filter_var($_POST['method'] ?? '', FILTER_SANITIZE_STRING));
        $address_type = trim(filter_var($_POST['address_type'] ?? '', FILTER_SANITIZE_STRING));
        
        // Xử lý địa chỉ
        $flat = trim(filter_var($_POST['flat'] ?? '', FILTER_SANITIZE_STRING));
        $street = trim(filter_var($_POST['street'] ?? '', FILTER_SANITIZE_STRING));
        $city = trim(filter_var($_POST['city'] ?? '', FILTER_SANITIZE_STRING));
        $country = trim(filter_var($_POST['country'] ?? '', FILTER_SANITIZE_STRING));
        $pincode = trim(filter_var($_POST['pincode'] ?? '', FILTER_SANITIZE_STRING));
        
        // 🔹 Validation cơ bản
        if (empty($name) || strlen($name) < 2) {
            $warning_msg[] = 'Tên không hợp lệ!';
        } elseif (empty($number) || strlen($number) < 10) {
            $warning_msg[] = 'Số điện thoại phải ít nhất 10 chữ số!';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $warning_msg[] = 'Email không hợp lệ!';
        } elseif (empty($method) || empty($address_type)) {
            $warning_msg[] = 'Vui lòng chọn phương thức thanh toán và loại địa chỉ!';
        } elseif (empty($flat) || empty($street) || empty($city) || empty($country) || empty($pincode)) {
            $warning_msg[] = 'Vui lòng điền đầy đủ thông tin địa chỉ!';
        } else {
            // Ghép địa chỉ đầy đủ
            $address = $flat . ', ' . $street . ', ' . $city . ', ' . $country . ' - ' . $pincode;
            
            // Tính toán tổng tiền và giảm giá
            $grand_total = 0;
            $discount = 0;
            $coupon_code = null;
            
            if (isset($_GET['get_id'])) {
                // Đặt hàng trực tiếp một sản phẩm
                $product_id = $_GET['get_id'];
                $select_product = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
                $select_product->execute([$product_id]);
                $product = $select_product->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    $grand_total = $product['price'];
                }
            } else {
                // Đặt hàng từ giỏ hàng
                $select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
                $select_cart->execute([$user_id]);
                
                if ($select_cart->rowCount() > 0) {
                    while ($cart_item = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                        $select_product = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
                        $select_product->execute([$cart_item['product_id']]);
                        $product = $select_product->fetch(PDO::FETCH_ASSOC);
                        
                        if ($product) {
                            $grand_total += $product['price'] * $cart_item['qty'];
                        }
                    }
                }
            }
            
            // Tính toán giảm giá nếu có mã
            if (isset($_SESSION['coupon']) && $grand_total > 0) {
                $coupon = $_SESSION['coupon'];
                $coupon_code = $coupon['code'];
                
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
                }
            }
            
            $final_total = $grand_total - $discount;
            
            if (isset($_GET['get_id'])) {
                // Đặt hàng trực tiếp một sản phẩm
                $product_id = $_GET['get_id'];
                $select_product = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
                $select_product->execute([$product_id]);
                $product = $select_product->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    // 🔹 Insert với thông tin mã giảm giá
                    $insert_order = $conn->prepare("INSERT INTO orders (user_id, name, number, email, method, address_type, address, product_id, price, qty, status, payment_status, coupon_code, discount_amount, final_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, ?, ?)");
                    $insert_order->execute([$user_id, $name, $number, $email, $method, $address_type, $address, $product_id, $product['price'], 1, $coupon_code, $discount, $final_total]);
                    
                    // Cập nhật số lần sử dụng mã giảm giá
                    if ($coupon_code) {
                        $update_used_count = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?");
                        $update_used_count->execute([$coupon_code]);
                    }
                    
                    $success_msg[] = 'Đơn hàng đã được đặt thành công!';
                } else {
                    $warning_msg[] = 'Sản phẩm không tồn tại!';
                }
            } else {
                // Đặt hàng từ giỏ hàng
                $select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
                $select_cart->execute([$user_id]);
                
                if ($select_cart->rowCount() > 0) {
                    while ($cart_item = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                        $select_product = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
                        $select_product->execute([$cart_item['product_id']]);
                        $product = $select_product->fetch(PDO::FETCH_ASSOC);
                        
                        if ($product) {
                            // Tính subtotal cho từng sản phẩm
                            $subtotal = $product['price'] * $cart_item['qty'];
                            $item_discount = 0;
                            
                            // Tính giảm giá tỷ lệ cho từng sản phẩm nếu có mã
                            if ($discount > 0) {
                                $item_discount = ($subtotal / $grand_total) * $discount;
                            }
                            
                            $item_final_price = $subtotal - $item_discount;
                            
                            // Insert từng item từ cart với thông tin mã giảm giá
                            $insert_order = $conn->prepare("INSERT INTO orders (user_id, name, number, email, method, address_type, address, product_id, price, qty, status, payment_status, coupon_code, discount_amount, final_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, ?, ?)");
                            $insert_order->execute([$user_id, $name, $number, $email, $method, $address_type, $address, $cart_item['product_id'], $product['price'], $cart_item['qty'], $coupon_code, $item_discount, $item_final_price]);
                        }
                    }
                    
                    // Cập nhật số lần sử dụng mã giảm giá
                    if ($coupon_code) {
                        $update_used_count = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?");
                        $update_used_count->execute([$coupon_code]);
                    }
                    
                    // Xóa giỏ hàng sau khi đặt hàng thành công
                    $delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                    $delete_cart->execute([$user_id]);
                    
                    // Xóa mã giảm giá khỏi session sau khi đặt hàng
                    unset($_SESSION['coupon']);
                    
                    $success_msg[] = 'Đơn hàng đã được đặt thành công!';
                } else {
                    $warning_msg[] = 'Giỏ hàng của bạn đang trống!';
                }
            }
        }
    }
}

// Tính tổng tiền và giảm giá để hiển thị
$grand_total = 0;
$discount = 0;
$final_total = 0;

if (isset($_GET['get_id'])) {
    // Xử lý khi checkout một sản phẩm trực tiếp
    $select_get = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $select_get->execute([$_GET['get_id']]);
    $fetch_get = $select_get->fetch(PDO::FETCH_ASSOC);
    
    if ($fetch_get) {
        $grand_total = $fetch_get['price'];
    }
} else {
    // Xử lý khi checkout từ Giỏ hàng
    $select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
    $select_cart->execute([$user_id]);

    if ($select_cart->rowCount() > 0) {
        while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
            $select_product = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
            $select_product->execute([$fetch_cart['product_id']]);
            $fetch_product = $select_product->fetch(PDO::FETCH_ASSOC);

            if ($fetch_product) {
                $sub_total = $fetch_cart['qty'] * $fetch_product['price'];
                $grand_total += $sub_total;
            }
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
/* CSS cho phần mã giảm giá trong checkout */
.coupon-section-checkout {
    margin: 1.5rem 0;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.coupon-form-checkout {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
}

.coupon-select-checkout {
    flex: 1;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    background-color: white;
    cursor: pointer;
    transition: border-color 0.3s;
}

.coupon-select-checkout:focus {
    outline: none;
    border-color: #28a745;
    box-shadow: 0 0 5px rgba(40, 167, 69, 0.3);
}

.coupon-form-checkout .btn {
    padding: 12px 20px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
    font-weight: 500;
}

.coupon-form-checkout .btn:hover {
    background: #218838;
}

.applied-coupon-checkout {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 5px;
    margin-bottom: 10px;
}

.applied-coupon-checkout p {
    margin: 0;
    color: #155724;
    font-weight: 500;
    font-size: 14px;
}

.remove-coupon-checkout .btn {
    padding: 8px 15px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.3s;
}

.remove-coupon-checkout .btn:hover {
    background: #c82333;
}

/* CSS cho phần tổng kết tiền trong checkout */
.summary .total-breakdown {
    margin-top: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.summary .total-breakdown p {
    display: flex;
    justify-content: space-between;
    margin: 0.8rem 0;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
    font-size: 15px;
}

.summary .total-breakdown .discount {
    color: #dc3545;
    font-weight: 500;
}

.summary .total-breakdown .final-total {
    font-size: 1.3rem;
    font-weight: bold;
    color: #28a745;
    border-top: 2px solid #28a745;
    margin-top: 1rem !important;
    padding-top: 1rem !important;
}

/* Responsive cho checkout */
@media (max-width: 768px) {
    .coupon-form-checkout {
        flex-direction: column;
    }
    
    .coupon-select-checkout {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .applied-coupon-checkout {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}
</style>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Coffee - Trang Thanh Toán</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
<?php include 'components/header.php'; ?>
<div class="main">
    <div class="banner">
        <h1>Tóm Tắt Thanh Toán</h1>
    </div>
    <div class="title2">
        <a href="home.php">Trang chủ</a><span>/ Tóm tắt thanh toán</span>
    </div>
    <section class="checkout">
        <div class="title">
            <img src="img/download.png" alt="Logo Green Coffee" class="logo">
            <h1>Tóm tắt thanh toán</h1>
            <p>Hoàn tất đơn hàng của bạn với thông tin thanh toán và giao hàng</p>
        </div>

        <div class="row">

            <!-- Phần tóm tắt đơn hàng -->
           <div class="summary">
    <h3>Giỏ hàng của tôi</h3>
    <div class="box-container">
        <?php
        if (isset($_GET['get_id'])) {
            // Xử lý khi checkout một sản phẩm trực tiếp
            $select_get = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
            $select_get->execute([$_GET['get_id']]);
            $fetch_get = $select_get->fetch(PDO::FETCH_ASSOC);
            
            if ($fetch_get) {
                $quantity = 1; 
                ?>
                <div class="flex">
                    <img src="img/<?= htmlspecialchars($fetch_get['image']); ?>" alt="<?= htmlspecialchars($fetch_get['name']); ?>" class="image">
                    <div>
                        <h3 class="name"><?= htmlspecialchars($fetch_get['name']); ?></h3>
                        <p class="price">$<?= number_format($fetch_get['price']); ?> x <?= $quantity; ?></p> 
                    </div>
                </div>
                <?php
            } else {
                echo '<p class="empty">Sản phẩm không tồn tại!</p>';
            }
        } else {
            // Xử lý khi checkout từ Giỏ hàng
            $select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
            $select_cart->execute([$user_id]);

            if ($select_cart->rowCount() > 0) {
                while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                    $product_id = $fetch_cart['product_id'];

                    if ($product_id) {
                        $select_product = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
                        $select_product->execute([$product_id]);
                        $fetch_product = $select_product->fetch(PDO::FETCH_ASSOC);

                        if ($fetch_product) {
                            ?>
                            <div class="flex">
                                <img src="img/<?= htmlspecialchars($fetch_product['image']); ?>" alt="<?= htmlspecialchars($fetch_product['name']); ?>" class="image">
                                <div>
                                    <h3 class="name"><?= htmlspecialchars($fetch_product['name']); ?></h3>
                                    <p class="price">$<?= number_format($fetch_product['price']); ?> x <?= $fetch_cart['qty']; ?></p>
                                </div>
                            </div>
                            <?php
                        }
                    }
                }
            } else {
                echo '<p class="empty">Giỏ hàng của bạn đang trống</p>';
            }
        }
        ?>
    </div>

    <!-- Phần mã giảm giá trong checkout -->
    <div class="coupon-section-checkout">
        <?php if (!isset($_SESSION['coupon'])) { ?>
            <form method="post" class="coupon-form-checkout">
                <select name="coupon_code" required class="coupon-select-checkout">
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
                <button type="submit" name="apply_coupon_checkout" class="btn">Áp dụng mã</button>
            </form>
        <?php } else { ?>
            <div class="applied-coupon-checkout">
                <p>
                    ✅ Mã giảm giá: <strong><?= $_SESSION['coupon']['code']; ?></strong> 
                    (<?= $_SESSION['coupon']['discount_type'] == 'percent' ? 
                    $_SESSION['coupon']['discount_value'] . '%' : 
                    '$' . $_SESSION['coupon']['discount_value']; ?>)
                </p>
                <form method="post" class="remove-coupon-checkout">
                    <button type="submit" name="remove_coupon_checkout" class="btn">Xóa mã</button>
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
</div>

            <!-- BILLING DETAILS FORM -->
            <form method="post">
                <h3>Thông tin thanh toán</h3>
                <div class="flex">
                    <div class="box">
                        <div class="input-field">
                            <p>Tên của bạn <span>*</span></p>
                            <input type="text" name="name" required maxlength="50" placeholder="Nhập Tên Của Bạn" class="input">
                        </div>
                        <div class="input-field">
                            <p>Số điện thoại của bạn <span>*</span></p>
                            <input type="number" name="number" required maxlength="50" placeholder="Nhập Số Điện Thoại Của Bạn" class="input">
                        </div>
                        <div class="input-field">
                            <p>Email của bạn <span>*</span></p>
                            <input type="email" name="email" required maxlength="50" placeholder="Nhập Email Của Bạn" class="input">
                        </div>
                        <div class="input-field">
                            <p>Phương thức thanh toán <span>*</span></p>
                            <select name="method" class="input" required>
                                <option value="">Chọn phương thức</option>
                                <option value="cash on delivery">Thanh toán khi nhận hàng</option>
                                <option value="credit or debit card">Thẻ tín dụng hoặc ghi nợ</option>
                                <option value="net banking">Chuyển khoản ngân hàng</option>
                                <option value="UPI or RuPay">UPI hoặc RuPay</option>
                                <option value="paytm">Paytm</option>
                            </select>
                        </div>
                        <div class="input-field">
                            <p>Loại địa chỉ <span>*</span></p>
                            <select name="address_type" class="input" required>
                                <option value="">Chọn loại địa chỉ</option>
                                <option value="home">Nhà riêng</option>
                                <option value="office">Văn phòng</option>
                            </select>
                        </div>
                    </div>
                    <div class="box">
                        <div class="input-field">
                            <p>Dòng địa chỉ 01 <span>*</span></p>
                            <input type="text" name="flat" required maxlength="50" placeholder="Ví dụ: Số nhà & tòa nhà" class="input">
                        </div>
                        <div class="input-field">
                            <p>Dòng địa chỉ 02 <span>*</span></p>
                            <input type="text" name="street" required maxlength="50" placeholder="Ví dụ: Tên đường" class="input">
                        </div>
                        <div class="input-field">
                            <p>Tên thành phố <span>*</span></p>
                            <input type="text" name="city" required maxlength="50" placeholder="Nhập tên thành phố của bạn" class="input">
                        </div>
                        <div class="input-field">
                            <p>Tên quốc gia <span>*</span></p>
                            <input type="text" name="country" required maxlength="50" placeholder="Nhập tên quốc gia của bạn" class="input">
                        </div>
                        <div class="input-field">
                            <p>Mã bưu điện <span>*</span></p>
                            <input type="number" name="pincode" required maxlength="6" placeholder="110022" min="0" max="999999" class="input">
                        </div>
                    </div>
                </div>
                <button type="submit" name="place_order" class="btn">Đặt hàng - $<?= number_format($final_total, 2); ?></button>
            </form>

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
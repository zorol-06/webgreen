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
            
            if (isset($_GET['get_id'])) {
                // Đặt hàng trực tiếp một sản phẩm
                $product_id = $_GET['get_id'];
                $select_product = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
                $select_product->execute([$product_id]);
                $product = $select_product->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    // 🔹 Insert với defaults (status='pending', payment_status='unpaid')
                    $insert_order = $conn->prepare("INSERT INTO orders (user_id, name, number, email, method, address_type, address, product_id, price, qty, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')");
                    $insert_order->execute([$user_id, $name, $number, $email, $method, $address_type, $address, $product_id, $product['price'], 1]);
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
                            // Insert từng item từ cart
                            $insert_order = $conn->prepare("INSERT INTO orders (user_id, name, number, email, method, address_type, address, product_id, price, qty, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')");
                            $insert_order->execute([$user_id, $name, $number, $email, $method, $address_type, $address, $cart_item['product_id'], $product['price'], $cart_item['qty']]);
                        }
                    }
                    
                    // Xóa giỏ hàng sau khi đặt hàng thành công
                    $delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                    $delete_cart->execute([$user_id]);
                    
                    $success_msg[] = 'Đơn hàng đã được đặt thành công!';
                } else {
                    $warning_msg[] = 'Giỏ hàng của bạn đang trống!';
                }
            }
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
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Quas aperiam ex neque eligendi, adipisci iste veritatis...</p>
        </div>

        <div class="row">

            <!-- MY BAG: đặt trước Billing Details -->
           <div class="summary">
    <h3>Giỏ hàng của tôi</h3>
    <div class="box-container">
        <?php
        $grand_total = 0;
        
        if (isset($_GET['get_id'])) {
            // Xử lý khi checkout một sản phẩm trực tiếp (qua get_id)
            $select_get = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
            $select_get->execute([$_GET['get_id']]);
            $fetch_get = $select_get->fetch(PDO::FETCH_ASSOC);
            
            if ($fetch_get) {
                $quantity = 1; 
                $sub_total = $fetch_get['price'] * $quantity;
                $grand_total += $sub_total;
                ?>
                <div class="flex">
                    <img src="img/<?= htmlspecialchars($fetch_get['image']); ?>" alt="<?= htmlspecialchars($fetch_get['name']); ?>" class="image">
                    <div>
                        <h3 class="name"><?= htmlspecialchars($fetch_get['name']); ?></h3>
                        <p class="price"><?= number_format($fetch_get['price']); ?> x <?= $quantity; ?></p> 
                    </div>
                </div>
                <?php
            } else {
                echo '<p class="empty">Sản phẩm không tồn tại!</p>';
            }
        } else {
            // Xử lý khi checkout từ Giỏ hàng (cart)
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
                            $sub_total = $fetch_cart['qty'] * $fetch_product['price'];
                            $grand_total += $sub_total;
                            ?>
                            <div class="flex">
                                <img src="img/<?= htmlspecialchars($fetch_product['image']); ?>" alt="<?= htmlspecialchars($fetch_product['name']); ?>" class="image">
                                <div>
                                    <h3 class="name"><?= htmlspecialchars($fetch_product['name']); ?></h3>
                                    <p class="price"><?= number_format($fetch_product['price']); ?> x <?= $fetch_cart['qty']; ?></p>
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
    <div class="grand-total"><span>Tổng số tiền phải thanh toán:</span>$<?= number_format($grand_total); ?>/-</div>
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
                <button type="submit" name="place_order" class="btn">Đặt hàng</button>
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
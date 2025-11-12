<?php
include 'components/connection.php';
session_start();
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
} else{
    $user_id='';
}

if(isset($_POST['logout'])){
    session_destroy();
    header("location: login.php");
    exit;
}

// Xử lý đặt hàng khi người dùng bấm Place Order
if(isset($_POST['place_order'])){
    if(empty($user_id)){
        $warning_msg[] = 'Vui lòng đăng nhập để đặt hàng';
    } else {
        // Lấy và lọc dữ liệu từ form
        $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
        $number = filter_var($_POST['number'] ?? '', FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $method = filter_var($_POST['method'] ?? '', FILTER_SANITIZE_STRING);
        $address_type = filter_var($_POST['address_type'] ?? '', FILTER_SANITIZE_STRING);
        
        // Xử lý địa chỉ
        $flat = filter_var($_POST['flat'] ?? '', FILTER_SANITIZE_STRING);
        $street = filter_var($_POST['street'] ?? '', FILTER_SANITIZE_STRING);
        $city = filter_var($_POST['city'] ?? '', FILTER_SANITIZE_STRING);
        $country = filter_var($_POST['country'] ?? '', FILTER_SANITIZE_STRING);
        $pincode = filter_var($_POST['pincode'] ?? '', FILTER_SANITIZE_STRING);
        
        // Ghép địa chỉ đầy đủ
        $address = $flat . ', ' . $street . ', ' . $city . ', ' . $country . ' - ' . $pincode;
        
        if(isset($_GET['get_id'])){
            // Đặt hàng trực tiếp một sản phẩm
            $product_id = $_GET['get_id'];
            $select_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $select_product->execute([$product_id]);
            $product = $select_product->fetch(PDO::FETCH_ASSOC);
            
            if($product){
                $insert_order = $conn->prepare("INSERT INTO orders (user_id, name, number, email, method, address_type, address, product_id, price, qty) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $insert_order->execute([$user_id, $name, $number, $email, $method, $address_type, $address, $product_id, $product['price'], 1]);
                $success_msg[] = 'Đơn hàng đã được đặt thành công!';
            }
        } else {
            // Đặt hàng từ giỏ hàng
            $select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            
            if($select_cart->rowCount() > 0){
                while($cart_item = $select_cart->fetch(PDO::FETCH_ASSOC)){
                    // Lấy thông tin sản phẩm từ giỏ hàng
                    $select_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
                    $select_product->execute([$cart_item['product_id']]);
                    $product = $select_product->fetch(PDO::FETCH_ASSOC);
                    
                    if($product){
                        // Thêm vào orders
                        $insert_order = $conn->prepare("INSERT INTO orders (user_id, name, number, email, method, address_type, address, product_id, price, qty) VALUES (?,?,?,?,?,?,?,?,?,?)");
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
?>
<style type="text/css">
  <?php include 'style.css'; ?>
</style>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Coffee - checkout Page</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
<?php include 'components/header.php'; ?>
<div class="main">
    <div class="banner">
        <h1>checkout summary</h1>
    </div>
    <div class="title2">
        <a href="home.php">home</a><span>/ checkout summary</span>
    </div>
    <section class="checkout">
        <div class="title">
            <img src="img/download.png" alt="" class="logo">
            <h1>checkout summary</h1>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Quas aperiam ex neque eligendi, adipisci iste veritatis...</p>
        </div>

        <div class="row">

            <!-- MY BAG: đặt trước Billing Details -->
           <div class="summary">
    <h3>my bag</h3>
    <div class="box-container">
        <?php
        $grand_total = 0;
        
        if(isset($_GET['get_id'])){
            // Xử lý khi checkout một sản phẩm trực tiếp (qua get_id)
            $select_get = $conn->prepare("SELECT * FROM products WHERE id=?");
            $select_get->execute([$_GET['get_id']]);
            while($fetch_get = $select_get->fetch(PDO::FETCH_ASSOC)){
                if($fetch_get){
                    // Giả định số lượng là 1 khi không có thông tin từ cart
                    $quantity = 1; 
                    $sub_total = $fetch_get['price'] * $quantity;
                    $grand_total += $sub_total;
                ?>
                <div class="flex">
                    <img src="img/<?=$fetch_get['image'];?>" alt="<?=$fetch_get['name'];?>" class="image">
                    <div>
                        <h3 class="name"><?=$fetch_get['name'];?></h3>
                        <p class="price"><?=$fetch_get['price'];?> x <?=$quantity;?></p> 
                    </div>
                </div>
                <?php
                }
            }
        } else {
            // Xử lý khi checkout từ Giỏ hàng (cart)
            $select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id=?");
            $select_cart->execute([$user_id]);

            if($select_cart->rowCount() > 0){
                while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){ // Vòng lặp giỏ hàng
                    
                    // Lấy ID sản phẩm từ giỏ hàng. Dòng này phải nằm TRONG vòng lặp.
                    $product_id = isset($fetch_cart['product_id']) ? $fetch_cart['product_id'] : null;

                    if ($product_id) {
                        $select_product = $conn->prepare("SELECT * FROM products WHERE id=?");
                        $select_product->execute([$product_id]);
                        $fetch_product = $select_product->fetch(PDO::FETCH_ASSOC);

                        // === FIX LỖI QUAN TRỌNG: KIỂM TRA DỮ LIỆU SẢN PHẨM (NULL/FALSE) ===
                        if($fetch_product){
                            $sub_total = $fetch_cart['qty'] * $fetch_product['price'];
                            $grand_total += $sub_total;
                        ?>
                        <div class="flex">
                            <img src="img/<?=$fetch_product['image'];?>" alt="<?=$fetch_product['name'];?>" class="image">
                            <div>
                                <h3 class="name"><?=$fetch_product['name'];?></h3>
                                <p class="price"><?=$fetch_product['price'];?> X <?=$fetch_cart['qty'];?></p>
                            </div>
                        </div>
                        <?php
                        } 
                    }
                } // Kết thúc while($fetch_cart)
            } else{
                echo '<p class="empty">your cart is empty</p>';
            }
        }
        ?>
    </div>
    <div class="grand-total"><span>total amount payable:</span>$<?=$grand_total?>/-</div>
</div>
            <!-- BILLING DETAILS FORM -->
            <form method="post">
                <h3>billing details</h3>
                <div class="flex">
                    <div class="box">
                        <div class="input-field">
                            <p> your name <span>*</span></p>
                            <input type="text" name="name" required maxlength="50" placeholder="Enter Your Name" class="input">
                        </div>
                        <div class="input-field">
                            <p> your number <span>*</span></p>
                            <input type="number" name="number" required maxlength="50" placeholder="Enter Your Number" class="input">
                        </div>
                        <div class="input-field">
                            <p> your email <span>*</span></p>
                            <input type="email" name="email" required maxlength="50" placeholder="Enter Your Email" class="input">
                        </div>
                        <div class="input-field">
                            <p>payment method <span>*</span></p>
                            <select name="method" class="input">
                                <option value="cash on delivery">cash on delivery</option>
                                <option value="credit or debit card">credit or debit card</option>
                                <option value="net banking">net banking</option>
                                <option value="UPI or RuPay">UPI or RuPay</option>
                                <option value="paytm">paytm</option>
                            </select>
                        </div>
                        <div class="input-field">
                            <p>address type <span>*</span></p>
                            <select name="address_type" class="input">
                                <option value="home">home</option>
                                <option value="office">office</option>
                            </select>
                        </div>
                    </div>
                    <div class="box">
                        <div class="input-field">
                            <p>address line 01 <span>*</span></p>
                            <input type="text" name="flat" maxlength="50" placeholder="e.g flat & building number" class="input">
                        </div>
                        <div class="input-field">
                            <p>address line 02 <span>*</span></p>
                            <input type="text" name="street" maxlength="50" placeholder="e.g street name" class="input">
                        </div>
                        <div class="input-field">
                            <p>city name <span>*</span></p>
                            <input type="text" name="city" maxlength="50" placeholder="Enter your city name" class="input">
                        </div>
                        <div class="input-field">
                            <p>Country name <span>*</span></p>
                            <input type="text" name="country" maxlength="50" placeholder="Enter your country name" class="input">
                        </div>
                        <div class="input-field">
                            <p>pincode <span>*</span></p>
                            <input type="number" name="pincode" maxlength="6" placeholder="110022" min="0" max="999999" class="input">
                        </div>
                    </div>
                </div>
                <button type="submit" name="place_order" class="btn">place order</button>
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
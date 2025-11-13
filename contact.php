<?php
// Kiểm tra và include connection.php
if (!file_exists('components/connection.php')) {
    die('Lỗi: Không tìm thấy file components/connection.php. Vui lòng tạo file này.');
}
include 'components/connection.php';

// Kiểm tra biến $conn có tồn tại và kết nối thành công không (với PDO)
if (!isset($conn) || !$conn) {
    die('Lỗi kết nối database: Biến $conn không được định nghĩa hoặc kết nối thất bại. Kiểm tra file connection.php.');
}

session_start();
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}
if(isset($_POST['logout'])){
    session_destroy();
    header("location: login.php");
    exit();  // Thêm exit() để đảm bảo không chạy tiếp
}

// Handle form submission với PDO Prepared Statements (an toàn hơn)
$message_alert = '';  // Biến để lưu thông báo
if(isset($_POST['submit-btn'])) {
    $name = trim($_POST['name'] ?? '');  // Sử dụng null coalescing operator ?? để tránh undefined key
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');  // Fix: Tránh warning nếu subject chưa submit
    $message = trim($_POST['message'] ?? '');
    
    // Validation nâng cao
    if(empty($name) || empty($email) || empty($subject) || empty($message)) {
        $message_alert = '<div class="message error"><span>click close to remove</span>All fields are required!</div>';  // Thêm class error cho CSS
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_alert = '<div class="message error"><span>click close to remove</span>Invalid email format!</div>';
    } else {
        try {
            // Prepared statement với PDO: An toàn tuyệt đối chống SQL injection
            $stmt = $conn->prepare("INSERT INTO message (user_id, name, email, subject, message) VALUES (:user_id, :name, :email, :subject, :message)");
            $user_id_insert = ($user_id != '') ? $user_id : null;
            $stmt->execute([
                ':user_id' => $user_id_insert,
                ':name' => $name,
                ':email' => $email,
                ':subject' => $subject,
                ':message' => $message
            ]);
            
            $message_alert = '<div class="message success"><span>click close to remove</span>Message sent successfully!</div>';
            $_POST = array();  // Reset form
        } catch (PDOException $e) {
            $message_alert = '<div class="message error"><span>click close to remove</span>Error executing query: ' . $e->getMessage() . '</div>';
        }
    }
}
?>
<style type="text/css">
  <?php include 'style.css'; ?>
  /* Thêm CSS cho alert nếu chưa có */
  .message { padding: 10px; margin: 10px 0; border-radius: 5px; }
  .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
  .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Coffee - Contact Us</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'components/header.php'; ?>
  <div class="main">
    <div class="banner">
        <h1>contact us</h1>
    </div>
    <div class="title2">
    <a href="home.php">home</a><span>contact us</span>
    </div>
      <section class="services">
        <div class="box-container">
        <div class="box">
        <img src="img/icon2.png">
        <div class="detail">
        <h3>great savings</h3>
        <p>save big every order</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon1.png">
        <div class="detail">
        <h3>24*7 support</h3>
        <p>one-on-one support</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon0.png">
        <div class="detail">
        <h3>gift vouchers</h3>
        <p>vouchers on every festivals</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon.png">
        <div class="detail">
        <h3>worldwide delivery</h3>
        <p>dropship worldwide</p>
        </div>
        </div>
        </div>
      </section>
      <div class="form-container">
        <?php if (!empty($message_alert)) { echo $message_alert; } ?>  <!-- Hiển thị alert ngay trong form -->
        <form method="post">
            <div class="title">
                <img src="img/download.png" class="logo">
                <h1>leave a message</h1>
            </div>
            <div class="input-field">
                <p>your name <span class="required">*</span></p>
                <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>  <!-- Thêm required và htmlspecialchars cho an toàn -->
            </div>
              <div class="input-field">
                <p>your email <span class="required">*</span></p>
                <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
              <div class="input-field">
                <p>subject <span class="required">*</span></p>
                <input type="text" name="subject" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" required>  <!-- Giữ nguyên name="subject" -->
            </div>
              <div class="input-field">
                <p>your message <span class="required">*</span></p>
                <textarea name="message" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
            </div>
             <button type="submit" name="submit-btn" class="btn">send message</button>
        </form>
      </div>
       <div class="address">
        <div class="title">
           <img src="img/download.png" class="logo">
            <h1>contact detail</h1>
            <p>Lorem, ipsum dolor sit amet consectetur adipisicing elit.</p>
        </div>
        <div class="box-container">
            <div class="box">
                <i class="bx bxs-map-pin"></i>
                <div>
                    <h4>address</h4>
                    <p>1092 Merigold Lane,Coral Way</p>
                </div>
            </div>
             <div class="box">
                <i class="bx bxs-phone-call"></i>
                <div>
                    <h4>phone number</h4>
                    <p>8866999955</p>
                </div>
            </div>
             <div class="box">
                <i class="bx bxs-envelope"></i>
                <div>
                    <h4>email</h4>
                    <p>selenaAnsari@gmail.com</p>
                </div>
            </div>
        </div>
        </div>
    <?php include 'components/footer.php'; ?>
      </div>
  <!-- Scripts -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="script.js"></script>
  <?php include 'components/alert.php'; ?>  <!-- Nếu alert.php dùng SweetAlert, có thể tích hợp JS ở đây -->
</body>
</html>
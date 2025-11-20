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
        $message_alert = '<div class="message error"><span>click close to remove</span>Tất cả các trường đều bắt buộc!</div>';  // Thêm class error cho CSS
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_alert = '<div class="message error"><span>click close to remove</span>Định dạng email không hợp lệ!</div>';
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
            
            $message_alert = '<div class="message success"><span>click close to remove</span>Tin nhắn đã được gửi thành công!</div>';
            $_POST = array();  // Reset form
        } catch (PDOException $e) {
            $message_alert = '<div class="message error"><span>click close to remove</span>Lỗi thực thi truy vấn: ' . $e->getMessage() . '</div>';
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
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Coffee - Liên hệ với chúng tôi</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'components/header.php'; ?>
  <div class="main">
    <div class="banner">
        <h1>liên hệ với chúng tôi</h1>
    </div>
    <div class="title2">
    <a href="home.php">trang chủ</a><span>liên hệ với chúng tôi</span>
    </div>
      <section class="services">
        <div class="box-container">
        <div class="box">
        <img src="img/icon2.png">
        <div class="detail">
        <h3>tiết kiệm lớn</h3>
        <p>tiết kiệm lớn mỗi đơn hàng</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon1.png">
        <div class="detail">
        <h3>hỗ trợ 24/7</h3>
        <p>hỗ trợ cá nhân một-một</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon0.png">
        <div class="detail">
        <h3>phiếu quà tặng</h3>
        <p>phiếu quà trên mọi lễ hội</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon.png">
        <div class="detail">
        <h3>giao hàng toàn cầu</h3>
        <p>giao hàng toàn thế giới</p>
        </div>
        </div>
        </div>
      </section>
      <div class="form-container">
        <?php if (!empty($message_alert)) { echo $message_alert; } ?>  <!-- Hiển thị alert ngay trong form -->
        <form method="post">
            <div class="title">
                <img src="img/download.png" class="logo">
                <h1>để lại tin nhắn</h1>
            </div>
            <div class="input-field">
                <p>tên của bạn <span class="required">*</span></p>
                <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>  <!-- Thêm required và htmlspecialchars cho an toàn -->
            </div>
              <div class="input-field">
                <p>email của bạn <span class="required">*</span></p>
                <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
              <div class="input-field">
                <p>chủ đề <span class="required">*</span></p>
                <input type="text" name="subject" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" required>  <!-- Giữ nguyên name="subject" -->
            </div>
              <div class="input-field">
                <p>tin nhắn của bạn <span class="required">*</span></p>
                <textarea name="message" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
            </div>
             <button type="submit" name="submit-btn" class="btn">gửi tin nhắn</button>
        </form>
      </div>
       <div class="address">
        <div class="title">
           <img src="img/download.png" class="logo">
            <h1>thông tin liên hệ</h1>
            <p></p>
        </div>
        <div class="box-container">
            <div class="box">
                <i class="bx bxs-map-pin"></i>
                <div>
                    <h4>địa chỉ</h4>
                    <p>Hòa Quý,Ngũ Hành Sơn,Đà Nẵng</p>
                </div>
            </div>
             <div class="box">
                <i class="bx bxs-phone-call"></i>
                <div>
                    <h4>số điện thoại</h4>
                    <p>0336965264</p>
                </div>
            </div>
             <div class="box">
                <i class="bx bxs-envelope"></i>
                <div>
                    <h4>email</h4>
                    <p>hoaiphm.24itb@vku.udn.vn</p>
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
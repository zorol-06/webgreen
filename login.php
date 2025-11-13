<?php
include 'components/connection.php'; 
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

// 🔹 Đăng nhập người dùng
if (isset($_POST['submit'])) {
    $email = $_POST['email'] ?? '';
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL)); // 🔹 Sửa: Trim và sanitize đúng cho email

    $pass = $_POST['pass'] ?? '';
    $pass = trim(filter_var($pass, FILTER_SANITIZE_STRING));

    // 🔹 Sửa: Validation cơ bản
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message[] = 'Email không hợp lệ!';
    } elseif (empty($pass) || strlen($pass) < 6) {
        $message[] = 'Mật khẩu phải ít nhất 6 ký tự!';
    } else {
        // lấy thông tin người dùng theo email
        $select_user = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $select_user->execute([$email]);
        $row = $select_user->fetch(PDO::FETCH_ASSOC);

        if ($select_user->rowCount() > 0) {
            $hashed_password = $row['password'];

            // Kiểm tra nếu mật khẩu trong DB là dạng hash (đã mã hóa)
            $password_info = password_get_info($hashed_password);
            if ($password_info['algo'] !== 0) { // 🔹 Sửa: Kiểm tra algo != 0 để xác nhận hashed
                // Kiểm tra đúng hash
                if (password_verify($pass, $hashed_password)) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['user_email'] = $row['email'];
                    $success_msg[] = 'Đăng nhập thành công!'; // 🔹 Thêm alert thành công nếu cần
                    header('location:home.php');
                    exit;
                } else {
                    $message[] = 'Sai mật khẩu!';
                }
            } else {
                // Nếu mật khẩu trong DB là dạng cũ (chưa hash)
                if ($pass === $hashed_password) {
                    // Đăng nhập thành công, rồi tự động hash lại mật khẩu trong DB
                    $new_hashed = password_hash($pass, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update->execute([$new_hashed, $row['id']]);

                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['user_email'] = $row['email'];
                    $success_msg[] = 'Đăng nhập thành công!'; // 🔹 Thêm alert thành công nếu cần
                    header('location:home.php');
                    exit;
                } else {
                    $message[] = 'Sai mật khẩu!';
                }
            }
        } else {
            $message[] = 'Email không tồn tại!';
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
    <title>Green Coffee - Đăng nhập</title>
</head>
<body>
    <div class="main-container">
       <section class="form-container">
        <div class="title">
            <img src="img/download.png">
            <h1>Đăng nhập ngay</h1>
            <p>Chào mừng quay lại Green Coffee!</p>
        </div>
        <form action="" method="post">
            <div class="input-field">
                <p>Email của bạn <span class="required">*</span></p>
                <input type="email" name="email" required placeholder="Nhập email của bạn" maxlength="50"
                oninput="this.value = this.value.replace(/\s/g,'')">        
            </div>  
            <div class="input-field">
                <p>Mật khẩu của bạn <span class="required">*</span></p>
                <input type="password" name="pass" required placeholder="Nhập mật khẩu của bạn" maxlength="50"
                 oninput="this.value = this.value.replace(/\s/g,'')">                   
            </div>  
            <input type="submit" name="submit" value="Đăng nhập ngay" class="btn">
            <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>    
        </form>
       </section>
    </div>
     <script src="components/sweetalert.js"></script>
    <script src="script.js"></script>
    <?php include 'components/alert.php'; ?>
</body>
</html>
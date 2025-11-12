<?php
include 'components/connection.php'; 
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

// login user
if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $email = filter_var($email, FILTER_SANITIZE_STRING);
    $pass = $_POST['pass'];
    $pass = filter_var($pass, FILTER_SANITIZE_STRING);

    // lấy thông tin người dùng theo email
    $select_user = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $select_user->execute([$email]);
    $row = $select_user->fetch(PDO::FETCH_ASSOC);

    if ($select_user->rowCount() > 0) {
        $hashed_password = $row['password'];

        // Kiểm tra nếu mật khẩu trong DB là dạng hash (đã mã hóa)
        if (password_get_info($hashed_password)['algo']) {
            // Kiểm tra đúng hash
            if (password_verify($pass, $hashed_password)) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_email'] = $row['email'];
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
?>
<style type="text/css">
  <?php include 'style.css'; ?>
</style>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green Coffee - Login</title>
</head>
<body>
    <div class="main-container">
       <section class="form-container">
        <div class="title">
            <img src="img/download.png">
            <h1>Login now</h1>
            <p>Welcome back to Green Coffee!</p>
        </div>
        <form action="" method="post">
            <div class="input-field">
                <p>Your email <span class="required">*</span></p>
                <input type="email" name="email" required placeholder="Enter your email" maxlength="50"
                oninput="this.value = this.value.replace(/\s/g,'')">        
            </div>  
            <div class="input-field">
                <p>Your password <span class="required">*</span></p>
                <input type="password" name="pass" required placeholder="Enter your password" maxlength="50"
                 oninput="this.value = this.value.replace(/\s/g,'')">                   
            </div>  
            <input type="submit" name="submit" value="Login now" class="btn">
            <p>Don't have an account? <a href="register.php">Register now</a></p>    
        </form>
       </section>
    </div>
     <script src="components/sweetalert.js"></script>
    <script src="script.js"></script>
    <?php include 'components/alert.php'; ?>
</body>
</html>

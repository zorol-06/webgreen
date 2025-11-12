<?php
include 'components/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

// đăng ký tài khoản mới
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);

    $email = $_POST['email'];
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    $pass = $_POST['pass'];
    $pass = filter_var($pass, FILTER_SANITIZE_STRING);

    $cpass = $_POST['cpass'];
    $cpass = filter_var($cpass, FILTER_SANITIZE_STRING);

    // kiểm tra email có tồn tại chưa
    $select = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $select->execute([$email]);

    if ($select->rowCount() > 0) {
        $message[] = 'Email đã được đăng ký, vui lòng dùng email khác!';
    } else {
        if ($pass != $cpass) {
            $message[] = 'Mật khẩu xác nhận không khớp!';
        } else {
            // mã hóa mật khẩu trước khi lưu
            $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

            $insert = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $insert->execute([$name, $email, $hashed_pass]);

            if ($insert) {
                $message[] = 'Đăng ký thành công! Bạn có thể đăng nhập ngay.';
            } else {
                $message[] = 'Đăng ký thất bại, vui lòng thử lại.';
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
    <title>green tea - register</title>
</head>
<body>
    <div class="main-container">
        <section class="form-container">
            <div class="title">
                <img src="img/download.png" alt="">
                <h1>Register now</h1>
                <p>Join us today to enjoy the best tea!</p>
            </div>

            <form action="" method="post">
                <div class="input-field">
                    <p>Your name <span class="required">*</span></p>
                    <input type="text" name="name" required placeholder="Enter your name" maxlength="50"
                           oninput="this.value = this.value.replace(/\s/g,'')">
                </div>

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

                <div class="input-field">
                    <p>Confirm password <span class="required">*</span></p>
                    <input type="password" name="cpass" required placeholder="Confirm your password" maxlength="50"
                           oninput="this.value = this.value.replace(/\s/g,'')">
                </div>

                <input type="submit" name="submit" value="register now" class="btn">
                <p>Already have an account? <a href="login.php">Login now</a></p>
            </form>
        </section>
    </div>

    <script src="components/sweetalert.js"></script>
    <script src="script.js"></script>
    <?php include 'components/alert.php'; ?>
</body>
</html>

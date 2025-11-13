<?php
include 'components/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

// 🔹 Đăng ký tài khoản mới
if (isset($_POST['submit'])) {
    $name = $_POST['name'] ?? '';
    $name = filter_var($name, FILTER_SANITIZE_STRING);
    $name = trim($name);

    $email = $_POST['email'] ?? '';
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $email = trim($email);

    $pass = $_POST['pass'] ?? '';
    $pass = filter_var($pass, FILTER_SANITIZE_STRING);
    $pass = trim($pass);

    $cpass = $_POST['cpass'] ?? '';
    $cpass = filter_var($cpass, FILTER_SANITIZE_STRING);
    $cpass = trim($cpass);

    // 🔹 Xử lý upload ảnh profile vào folder img/ chính (không cần subfolder)
    $profile_image = NULL;
    $upload_dir = 'img/'; // Upload trực tiếp vào img/ (không subfolder)

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $file_name = $_FILES['profile_image']['name'];
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif']; // Chỉ ảnh
        
        if (in_array($file_ext, $allowed) && $_FILES['profile_image']['size'] < 5000000) { // <5MB
            $new_name = 'user_' . time() . '_' . uniqid() . '.' . $file_ext; // Tên unique
            $upload_path = $upload_dir . $new_name; // Đường dẫn: img/user_123456.jpg
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $profile_image = $upload_path; // Lưu đường dẫn tương đối vào DB
            } else {
                $message[] = 'Lỗi upload ảnh, kiểm tra quyền folder img/!';
            }
        } else {
            $message[] = 'Chỉ chấp nhận ảnh JPG/PNG/GIF dưới 5MB!';
        }
    }

    // 🔹 Validation cơ bản với thông báo lỗi cụ thể
    if (empty($name) || strlen($name) < 2 || strlen($name) > 50) {
        $message[] = 'Tên phải từ 2-50 ký tự!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message[] = 'Email không hợp lệ!';
    } elseif (strlen($pass) < 6) {
        $message[] = 'Mật khẩu phải ít nhất 6 ký tự!';
    } elseif (strlen($pass) > 50) {
        $message[] = 'Mật khẩu không được vượt quá 50 ký tự!';
    } else {
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

                // Insert với profile_image nếu có
                $insert = $conn->prepare("INSERT INTO users (name, email, password, profile_image, user_type) VALUES (?, ?, ?, ?, 'user')");
                $insert->execute([$name, $email, $hashed_pass, $profile_image]);

                if ($insert) {
                    $success_msg[] = 'Đăng ký thành công! Bạn có thể đăng nhập ngay.';
                } else {
                    $message[] = 'Đăng ký thất bại, vui lòng thử lại.';
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
    <title>Green Coffee - Đăng ký</title>
</head>
<body>
    <div class="main-container">
        <section class="form-container">
            <div class="title">
                <img src="img/download.png" alt="Logo Green Coffee">
                <h1>Đăng ký ngay</h1>
                <p>Tham gia ngay để thưởng thức cà phê tốt nhất!</p>
            </div>

            <!-- Hiển thị thông báo lỗi/thành công -->
            <?php if (isset($message) && !empty($message)): ?>
                <div class="error-messages" style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($message as $msg): ?>
                            <li><?= htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($success_msg) && !empty($success_msg)): ?>
                <div class="success-messages" style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($success_msg as $msg): ?>
                            <li><?= htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <div class="input-field">
                    <p>Tên của bạn <span class="required">*</span></p>
                    <input type="text" name="name" value="<?= htmlspecialchars($name ?? ''); ?>" required placeholder="Nhập tên của bạn" maxlength="50"
                           oninput="this.value = this.value.replace(/\s{2,}/g,' ').trim()">
                </div>

                <div class="input-field">
                    <p>Email của bạn <span class="required">*</span></p>
                    <input type="email" name="email" value="<?= htmlspecialchars($email ?? ''); ?>" required placeholder="Nhập email của bạn" maxlength="50"
                           oninput="this.value = this.value.replace(/\s/g,'')">
                </div>

                <div class="input-field">
                    <p>Mật khẩu của bạn <span class="required">*</span></p>
                    <input type="password" name="pass" required placeholder="Nhập mật khẩu của bạn" maxlength="50"
                           oninput="this.value = this.value.replace(/\s/g,'')">
                </div>

                <div class="input-field">
                    <p>Xác nhận mật khẩu <span class="required">*</span></p>
                    <input type="password" name="cpass" required placeholder="Xác nhận mật khẩu" maxlength="50"
                           oninput="this.value = this.value.replace(/\s/g,'')">
                </div>

                <!-- Phần chọn ảnh profile -->
                <div class="input-field">
                    <p>Ảnh đại diện (tùy chọn)</p>
                    <input type="file" name="profile_image" accept="image/*">
                    <small style="color: gray;">Chấp nhận JPG, PNG dưới 5MB</small>
                </div>

                <input type="submit" name="submit" value="Đăng ký ngay" class="btn">
                <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
            </form>
        </section>
    </div>

    <script src="components/sweetalert.js"></script>
    <script src="script.js"></script>
    <?php include 'components/alert.php'; ?>
</body>
</html>
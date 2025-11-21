<?php
include 'components/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

// 🔹 THÊM DÒNG NÀY - Include file chứa hàm sendMail
include 'functions.php';

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
                    // 🔹 GỬI EMAIL THÔNG BÁO ĐĂNG KÝ THÀNH CÔNG
                    $emailSubject = 'Chào mừng đến với Green Coffee!';
                    
                    $emailContent = "
                        <html>
                        <head>
                            <style>
                                body { 
                                    font-family: Arial, sans-serif; 
                                    line-height: 1.6;
                                    color: #333;
                                    max-width: 600px;
                                    margin: 0 auto;
                                    padding: 20px;
                                }
                                .header { 
                                    background: linear-gradient(135deg, #2e7d32, #4caf50);
                                    color: white; 
                                    padding: 30px 20px; 
                                    text-align: center; 
                                    border-radius: 10px 10px 0 0;
                                }
                                .header h1 { 
                                    margin: 0; 
                                    font-size: 28px;
                                }
                                .content { 
                                    padding: 30px 20px; 
                                    background: #f9f9f9; 
                                    border-left: 1px solid #ddd;
                                    border-right: 1px solid #ddd;
                                }
                                .welcome-text {
                                    font-size: 18px;
                                    color: #2e7d32;
                                    margin-bottom: 20px;
                                }
                                .user-info {
                                    background: white;
                                    padding: 20px;
                                    border-radius: 8px;
                                    border-left: 4px solid #4caf50;
                                    margin: 20px 0;
                                }
                                .user-info ul {
                                    margin: 0;
                                    padding-left: 20px;
                                }
                                .user-info li {
                                    margin-bottom: 8px;
                                }
                                .login-btn {
                                    display: inline-block;
                                    background: linear-gradient(135deg, #2e7d32, #4caf50);
                                    color: white; 
                                    padding: 14px 30px; 
                                    text-decoration: none; 
                                    border-radius: 25px;
                                    font-weight: bold;
                                    margin: 20px 0;
                                    text-align: center;
                                }
                                .footer { 
                                    text-align: center; 
                                    padding: 20px; 
                                    font-size: 12px; 
                                    color: #666;
                                    background: #f1f1f1;
                                    border-radius: 0 0 10px 10px;
                                    border-left: 1px solid #ddd;
                                    border-right: 1px solid #ddd;
                                    border-bottom: 1px solid #ddd;
                                }
                                .highlight {
                                    background: #e8f5e9;
                                    padding: 15px;
                                    border-radius: 8px;
                                    margin: 15px 0;
                                }
                            </style>
                        </head>
                        <body>
                            <div class='header'>
                                <h1>☕ Green Coffee</h1>
                                <p style='margin: 10px 0 0 0; opacity: 0.9;'>Thế giới cà phê nguyên chất</p>
                            </div>
                            <div class='content'>
                                <div class='welcome-text'>
                                    <strong>Xin chào $name!</strong>
                                </div>
                                
                                <p>Cảm ơn bạn đã đăng ký tài khoản tại <strong>Green Coffee</strong> - nơi mang đến những hạt cà phê chất lượng nhất!</p>
                                
                                <div class='user-info'>
                                    <p><strong>Thông tin tài khoản của bạn:</strong></p>
                                    <ul>
                                        <li><strong>👤 Tên:</strong> $name</li>
                                        <li><strong>📧 Email:</strong> $email</li>
                                        <li><strong>📅 Ngày đăng ký:</strong> " . date('d/m/Y H:i:s') . "</li>
                                        <li><strong>🔐 Trạng thái:</strong> Đã kích hoạt</li>
                                    </ul>
                                </div>

                                <div class='highlight'>
                                    <p><strong>🎉 Chào mừng bạn đến với cộng đồng Green Coffee!</strong></p>
                                    <p>Bây giờ bạn có thể:</p>
                                    <ul>
                                        <li>🛒 Mua sắm các sản phẩm cà phê đặc biệt</li>
                                        <li>⭐ Đánh giá và nhận xét sản phẩm</li>
                                        <li>📦 Theo dõi đơn hàng dễ dàng</li>
                                        <li>🎁 Nhận các ưu đãi đặc biệt</li>
                                    </ul>
                                </div>

                                <div style='text-align: center; margin: 30px 0;'>
                                    <a href='http://localhost/Green-Coffee/login.php' class='login-btn'>
                                        🚀 Bắt đầu mua sắm ngay
                                    </a>
                                </div>

                                <p>Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi qua email này hoặc gọi hotline: <strong>1900 1234</strong></p>
                                
                                <p>Trân trọng,<br>
                                <strong>Đội ngũ Green Coffee</strong></p>
                            </div>
                            <div class='footer'>
                                <p>© " . date('Y') . " <strong>Green Coffee</strong>. All rights reserved.</p>
                                <p>Địa chỉ: 123 Đường Cà Phê, Quận 1, TP.HCM</p>
                                <p>Hotline: 1900 1234 | Email: support@greencoffee.com</p>
                                <p><em>Đây là email tự động, vui lòng không trả lời.</em></p>
                            </div>
                        </body>
                        </html>
                    ";

                    // Gọi hàm sendMail để gửi email thông báo
                    $emailSent = sendMail($email, $emailSubject, $emailContent);

                    if ($emailSent) {
                        $success_msg[] = 'Đăng ký thành công! Email xác nhận đã được gửi tới bạn.';
                    } else {
                        $success_msg[] = 'Đăng ký thành công! (Có lỗi khi gửi email xác nhận)';
                    }
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
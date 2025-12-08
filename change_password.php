<?php
include 'components/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
    header("location: login.php");
    exit;
}

// Lấy thông tin người dùng
$select_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$select_user->execute([$user_id]);
$user = $select_user->fetch(PDO::FETCH_ASSOC);

// Biến thông báo
$message = '';
$message_type = ''; // success, error

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Kiểm tra mật khẩu hiện tại
    if (!password_verify($current_password, $user['password'])) {
        $message = 'Mật khẩu hiện tại không chính xác!';
        $message_type = 'error';
    } 
    // Kiểm tra mật khẩu mới và xác nhận
    elseif ($new_password !== $confirm_password) {
        $message = 'Mật khẩu mới và xác nhận mật khẩu không khớp!';
        $message_type = 'error';
    } 
    // Kiểm tra độ dài mật khẩu mới
    elseif (strlen($new_password) < 6) {
        $message = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
        $message_type = 'error';
    } 
    // Kiểm tra mật khẩu mới không được giống mật khẩu cũ
    elseif (password_verify($new_password, $user['password'])) {
        $message = 'Mật khẩu mới không được giống mật khẩu cũ!';
        $message_type = 'error';
    }
    // Thực hiện đổi mật khẩu
    else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_password = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_password->execute([$hashed_password, $user_id]);
        
        if ($update_password->rowCount() > 0) {
            $message = 'Đổi mật khẩu thành công!';
            $message_type = 'success';
            
            // Reset form
            $_POST = array();
        } else {
            $message = 'Có lỗi xảy ra, vui lòng thử lại!';
            $message_type = 'error';
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
    <title>Đổi Mật Khẩu - Green Coffee</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .change-password-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .change-password-header {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .change-password-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .change-password-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .change-password-body {
            padding: 40px;
        }

        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
            border: 3px solid #2ecc71;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-details h3 {
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 20px;
        }

        .user-details p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 15px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 20px;
        }

        .input-with-icon input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .input-with-icon input:focus {
            outline: none;
            border-color: #2ecc71;
            background: white;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }

        .input-with-icon input.error {
            border-color: #e74c3c;
        }

        .input-with-icon input.success {
            border-color: #2ecc71;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #7f8c8d;
            cursor: pointer;
            font-size: 20px;
        }

        .password-toggle:hover {
            color: #2c3e50;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .strength-bar {
            flex: 1;
            height: 4px;
            background: #eee;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            background: #e74c3c;
            transition: all 0.3s ease;
        }

        .strength-fill.weak {
            width: 33%;
            background: #e74c3c;
        }

        .strength-fill.medium {
            width: 66%;
            background: #f39c12;
        }

        .strength-fill.strong {
            width: 100%;
            background: #2ecc71;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message i {
            font-size: 20px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 16px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(46, 204, 113, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #d0d0d0;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .password-rules {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #2ecc71;
        }

        .password-rules h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .password-rules ul {
            list-style: none;
            padding-left: 0;
        }

        .password-rules li {
            margin-bottom: 8px;
            color: #6c757d;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-rules li i {
            color: #2ecc71;
            font-size: 12px;
        }

        @media (max-width: 576px) {
            .change-password-container {
                margin: 20px;
            }
            
            .change-password-body {
                padding: 30px 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
            }
            
            .user-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="main">
        <div class="change-password-container">
            <div class="change-password-header">
                <h1><i class='bx bx-key'></i> Đổi Mật Khẩu</h1>
                <p>Bảo vệ tài khoản của bạn bằng mật khẩu mạnh</p>
            </div>
            
            <div class="change-password-body">
                <!-- Thông tin người dùng -->
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_image']); ?>" alt="Avatar">
                        <?php else: ?>
                            <img src="img/default-avatar.jpg" alt="Avatar">
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <h3><?= htmlspecialchars($user['name']); ?></h3>
                        <p><?= htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <!-- Hiển thị thông báo -->
                <?php if ($message): ?>
                    <div class="message <?= $message_type; ?>">
                        <i class='bx <?= $message_type == 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
                        <span><?= htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Form đổi mật khẩu -->
                <form method="POST" action="" id="changePasswordForm">
                    <div class="form-group">
                        <label for="current_password"><i class='bx bx-lock-alt'></i> Mật khẩu hiện tại</label>
                        <div class="input-with-icon">
                            <i class='bx bx-lock'></i>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   required
                                   placeholder="Nhập mật khẩu hiện tại"
                                   <?= $message_type == 'error' && isset($_POST['current_password']) ? 'class="error"' : ''; ?>
                                   value="<?= isset($_POST['current_password']) ? htmlspecialchars($_POST['current_password']) : ''; ?>">
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                <i class='bx bx-hide'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password"><i class='bx bx-lock-open-alt'></i> Mật khẩu mới</label>
                        <div class="input-with-icon">
                            <i class='bx bx-key'></i>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   required
                                   placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)"
                                   oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                <i class='bx bx-hide'></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <span id="strength-text">Độ mạnh mật khẩu</span>
                            <div class="strength-bar">
                                <div class="strength-fill" id="strength-fill"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><i class='bx bx-lock-open-alt'></i> Xác nhận mật khẩu mới</label>
                        <div class="input-with-icon">
                            <i class='bx bx-key'></i>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   required
                                   placeholder="Nhập lại mật khẩu mới">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class='bx bx-hide'></i>
                            </button>
                        </div>
                        <div id="password-match" style="font-size: 13px; margin-top: 5px;"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class='bx bx-check-circle'></i> Đổi Mật Khẩu
                        </button>
                        <a href="profile.php" class="btn btn-secondary">
                            <i class='bx bx-arrow-back'></i> Quay Lại
                        </a>
                    </div>
                </form>
                
                <!-- Quy tắc mật khẩu -->
                <div class="password-rules">
                    <h4><i class='bx bx-info-circle'></i> Quy tắc mật khẩu an toàn:</h4>
                    <ul>
                        <li><i class='bx bx-check'></i> Ít nhất 6 ký tự</li>
                        <li><i class='bx bx-check'></i> Không nên giống mật khẩu cũ</li>
                        <li><i class='bx bx-check'></i> Kết hợp chữ hoa, chữ thường và số</li>
                        <li><i class='bx bx-check'></i> Tránh sử dụng thông tin cá nhân</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
    
    <script>
        // Hiển thị/ẩn mật khẩu
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggleBtn = input.nextElementSibling;
            const icon = toggleBtn.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bx bx-show';
            } else {
                input.type = 'password';
                icon.className = 'bx bx-hide';
            }
        }
        
        // Kiểm tra độ mạnh mật khẩu
        function checkPasswordStrength(password) {
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-text');
            
            let strength = 0;
            
            // Kiểm tra độ dài
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            
            // Kiểm tra có số không
            if (/\d/.test(password)) strength++;
            
            // Kiểm tra có chữ thường và chữ hoa
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            
            // Kiểm tra có ký tự đặc biệt
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Cập nhật giao diện
            strengthFill.className = 'strength-fill';
            if (password.length === 0) {
                strengthText.textContent = 'Độ mạnh mật khẩu';
                strengthFill.style.width = '0%';
            } else if (strength < 3) {
                strengthText.textContent = 'Yếu';
                strengthFill.classList.add('weak');
            } else if (strength < 5) {
                strengthText.textContent = 'Trung bình';
                strengthFill.classList.add('medium');
            } else {
                strengthText.textContent = 'Mạnh';
                strengthFill.classList.add('strong');
            }
            
            // Kiểm tra trùng khớp mật khẩu
            checkPasswordMatch();
        }
        
        // Kiểm tra mật khẩu có khớp không
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchDiv.textContent = '';
                matchDiv.style.color = '';
            } else if (newPassword === confirmPassword) {
                matchDiv.innerHTML = '<i class="bx bx-check" style="color:#2ecc71"></i> Mật khẩu khớp';
                matchDiv.style.color = '#2ecc71';
            } else {
                matchDiv.innerHTML = '<i class="bx bx-x" style="color:#e74c3c"></i> Mật khẩu không khớp';
                matchDiv.style.color = '#e74c3c';
            }
        }
        
        // Thêm sự kiện cho input xác nhận mật khẩu
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
        // Kiểm tra form trước khi submit
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Mật khẩu mới và xác nhận mật khẩu không khớp!');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Mật khẩu mới phải có ít nhất 6 ký tự!');
                return false;
            }
            
            return true;
        });
        
        // Tự động kiểm tra khi trang tải xong
        document.addEventListener('DOMContentLoaded', function() {
            checkPasswordStrength(document.getElementById('new_password').value);
            checkPasswordMatch();
        });
    </script>
</body>
</html>
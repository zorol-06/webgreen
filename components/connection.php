<?php
$db_name = 'mysql:host=localhost;dbname=green_coffee;charset=utf8mb4';
$db_user = 'root';
$db_password = '';

try {
    $conn = new PDO($db_name, $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Kết nối cơ sở dữ liệu thất bại: ' . $e->getMessage());
}

// 🔹 Hàm tạo ID ngẫu nhiên
if (!function_exists('unique_id')) {
    function unique_id() {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charLength = strlen($chars);
        $randomString = '';
        for ($i = 0; $i < 20; $i++) {
            $randomString .= $chars[mt_rand(0, $charLength - 1)];
        }
        return $randomString;
    }
}
?>

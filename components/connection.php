<?php
$db_name = 'mysql:host=localhost;dbname=shop_db;charset=utf8mb4';
$db_user = 'root';
$db_password = '';

$conn = new PDO($db_name, $db_user, $db_password);


function unique_id()
{
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charLength = strlen($chars);
    $ramdomString = '';
    for ($i = 0; $i < 20; $i++) {
        $ramdomString .= $chars[mt_rand(0, $charLength - 1)];
    }
    return $ramdomString;
}
?>
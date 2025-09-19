<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] != 1) {
    header("Location: login.php");
    exit;
}

$total_price = isset($_GET['total_price']) ? $_GET['total_price'] : 0;
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單確認</title>
</head>
<body>
    <h1>訂單確認</h1>
    <p>您的訂單已成功提交。</p>
    <a href="user.php">返回首頁</a>
    <a href="orders.php">查看訂單</a>
</body>
</html>

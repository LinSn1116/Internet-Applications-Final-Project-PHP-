<?php
session_start();
$link = @mysqli_connect('localhost', 'root', '', 'ecommerce');

if (!$link) {
    die('Error: ' . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = mysqli_prepare($link, "SELECT id, username, is_admin FROM users WHERE username = ? AND password = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $username, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['login'] = 1;
        $_SESSION['username'] = $row['username'];
        $_SESSION['is_admin'] = $row['is_admin'];
        $_SESSION['user_id'] = $row['id']; // 設置 user_id
        if ($row['is_admin']) {
            header("Location: admin.php"); // 管理員重定向到管理員介面
        } else {
            header("Location: user.php"); // 普通用戶重定向到用戶介面
        }
        exit;
    } else {
        $error = "無效的用戶名或密碼";
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用戶登錄</title>
</head>
<body>
    <h1>用戶登錄</h1>
    <form action="login.php" method="post">
        <label for="username">帳號：</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">密碼：</label>
        <input type="password" id="password" name="password" required>
        <br>
        <input type="submit" value="登錄">
        <input type="button" value="註冊" onclick="window.location.href='register.php'"> <!-- 添加註冊按鈕 -->
    </form>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
</body>
</html>

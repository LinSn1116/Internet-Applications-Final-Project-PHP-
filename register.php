<?php
mysqli_report(MYSQLI_REPORT_OFF);
$link = @mysqli_connect('localhost', 'root', '', 'ecommerce');

$username_error = "";
$password_setting_error = "";
$password_checking_error = "";
$error = "";
$register_success = "";

if (!$link) {
    die('Error:' . mysqli_connect_error());
}

if (isset($_POST["register"])) {
    $username = $_POST["username"];
    $password_setting = $_POST["password_setting"];
    $password_checking = $_POST["password_checking"];

    if (empty($username)) {
        $username_error = "<font color='red'>使用者名稱不可為空值。</font>";
    }
    if (empty($password_setting)) {
        $password_setting_error = "<font color='red'>密碼不可為空值。</font>";
    }
    if (empty($password_checking)) {
        $password_checking_error = "<font color='red'>密碼確認不可為空值。</font>";
    }
    if ($password_setting !== $password_checking) {
        $password_checking_error = "<font color='red'>密碼和密碼確認必須一致。</font>";
    } else {
        $stmt = mysqli_prepare($link, "SELECT username FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "<font color='red'>使用者名稱已存在，請重新輸入。</font>";
        } else {
            if (empty($username_error) && empty($password_setting_error) && empty($password_checking_error) && empty($error)) {
                $stmt = mysqli_prepare($link, "INSERT INTO users (username, password) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ss', $username, $password_setting);
                if (mysqli_stmt_execute($stmt)) {
                    $register_success = "<font color='green'>註冊成功!</font>";
                    header("refresh:2;url=login.php");
                    echo $register_success . " 正在跳轉至登入介面。";
                    exit;
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
}
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <form action="register.php" method="post">
        <table>
            <tr><td>帳號：</td><td><input type="text" name="username"></td></tr>
            <tr><td>密碼：</td><td><input type="password" name="password_setting"></td></tr>
            <tr><td>確認密碼：</td><td><input type="password" name="password_checking"></td></tr>
            <tr><td></td><td><?php echo $username_error . $password_setting_error . $password_checking_error . $error; ?></td></tr>
            <tr><td><input type="submit" name="register" value="註冊"></td><td><input type="button" value="取消" onclick="window.location.href='login.php'"></td></tr> <!-- 添加取消按鈕 -->
        </table>
    </form>
</body>
</html>

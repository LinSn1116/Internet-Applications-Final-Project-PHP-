<?php
session_start();
error_reporting(0);
if (!isset($_SESSION['login']) || $_SESSION['login'] != 1) {
    header("Location: login.php");
    exit;
}

$link = @mysqli_connect('localhost', 'root', '', 'ecommerce');

if (!$link) {
    die('Error: ' . mysqli_connect_error());
}

$message = "";

if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $username = $_SESSION['username'];

    // 獲取用戶ID
    $stmt_user = mysqli_prepare($link, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt_user, 's', $username);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $row_user = mysqli_fetch_assoc($result_user);
    $user_id = $row_user['id'];
    mysqli_stmt_close($stmt_user);

    // 檢查產品是否已經在購物車中
    $stmt_cart_check = mysqli_prepare($link, "SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt_cart_check, 'ii', $user_id, $product_id);
    mysqli_stmt_execute($stmt_cart_check);
    $result_cart_check = mysqli_stmt_get_result($stmt_cart_check);

    if (mysqli_num_rows($result_cart_check) > 0) {
        // 如果產品已經在購物車中，更新數量
        $stmt_update_cart = mysqli_prepare($link, "UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt_update_cart, 'iii', $quantity, $user_id, $product_id);
        if (mysqli_stmt_execute($stmt_update_cart)) {
            $message = "<font color='green'>產品已成功添加到購物車。</font>";
        } else {
            $message = "<font color='red'>無法添加產品到購物車，請再試一次。</font>";
        }
        mysqli_stmt_close($stmt_update_cart);
    } else {
        // 如果產品不在購物車中，插入新記錄
        $stmt_add_to_cart = mysqli_prepare($link, "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt_add_to_cart, 'iii', $user_id, $product_id, $quantity);
        if (mysqli_stmt_execute($stmt_add_to_cart)) {
            $message = "<font color='green'>產品已成功添加到購物車。</font>";
        } else {
            $message = "<font color='red'>無法添加產品到購物車，請再試一次。</font>";
        }
        mysqli_stmt_close($stmt_add_to_cart);
    }

    mysqli_stmt_close($stmt_cart_check);
}

// 獲取所有產品
$query = "SELECT * FROM products";
$result = mysqli_query($link, $query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用戶首頁</title>
</head>
<body>
    <h1>歡迎，<?php echo $_SESSION['username']; ?>!</h1>
    <?php echo $message; ?>
    <a href="cart.php">查看購物車</a>
    <a href="orders.php">我的訂單</a>
    <form method="POST" action="logout.php">
        <button type="submit">登出</button>
    </form>
    <h2>產品列表</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>名稱</th>
            <th>描述</th>
            <th>價格</th>
            <th>庫存</th>
            <th>類別</th>
            <th>標籤</th>
            <th>數量</th>
            <th>操作</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <form action="user.php" method="post">
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['description']; ?></td>
                <td><?php echo $row['price']; ?></td>
                <td><?php echo $row['stock']; ?></td>
                <td><?php echo $row['category']; ?></td>
                <td><?php echo $row['tags']; ?></td>
                <td><input type="number" name="quantity" value="1" min="1" max="<?php echo $row['stock']; ?>"></td>
                <td>
                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                    <input type="submit" name="add_to_cart" value="添加到購物車">
                </td>
            </form>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php
mysqli_free_result($result);
mysqli_close($link);
?>

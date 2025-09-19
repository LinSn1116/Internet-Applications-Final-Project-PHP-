<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] != 1) {
    header("Location: login.php");
    exit;
}

$link = @mysqli_connect('localhost', 'root', '', 'ecommerce');

if (!$link) {
    die('Error: ' . mysqli_connect_error());
}

$message = "";

if (isset($_POST['update_cart'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = $_POST['quantity'];
    $stmt_update_cart = mysqli_prepare($link, "UPDATE cart SET quantity = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_update_cart, 'ii', $quantity, $cart_id);
    if (mysqli_stmt_execute($stmt_update_cart)) {
        $message = "<font color='green'>購物車已更新。</font>";
    } else {
        $message = "<font color='red'>無法更新購物車，請再試一次。</font>";
    }
    mysqli_stmt_close($stmt_update_cart);
}

if (isset($_POST['delete_from_cart'])) {
    $cart_id = $_POST['cart_id'];
    $stmt_delete_cart = mysqli_prepare($link, "DELETE FROM cart WHERE id = ?");
    mysqli_stmt_bind_param($stmt_delete_cart, 'i', $cart_id);
    if (mysqli_stmt_execute($stmt_delete_cart)) {
        $message = "<font color='green'>產品已從購物車中刪除。</font>";
    } else {
        $message = "<font color='red'>無法從購物車中刪除產品，請再試一次。</font>";
    }
    mysqli_stmt_close($stmt_delete_cart);
}

if (isset($_POST['checkout'])) {
    $user_id = $_SESSION['username'];
    // 獲取用戶ID
    $stmt_user = mysqli_prepare($link, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt_user, 's', $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $row_user = mysqli_fetch_assoc($result_user);
    $user_id = $row_user['id'];
    mysqli_stmt_close($stmt_user);

    $total_price = 0;
    $cart_items = [];

    // 獲取購物車中的產品
    $stmt_cart = mysqli_prepare($link, "SELECT cart.id as cart_id, products.id as product_id, products.price, cart.quantity FROM cart JOIN products ON cart.product_id = products.id WHERE cart.user_id = ?");
    mysqli_stmt_bind_param($stmt_cart, 'i', $user_id);
    mysqli_stmt_execute($stmt_cart);
    $result_cart = mysqli_stmt_get_result($stmt_cart);

    while ($row_cart = mysqli_fetch_assoc($result_cart)) {
        $total_price += $row_cart['price'] * $row_cart['quantity'];
        $cart_items[] = $row_cart;
    }
    mysqli_stmt_close($stmt_cart);

    if (empty($cart_items)) {
        $message = "<font color='red'>您的購物車是空的，無法生成訂單。</font>";
    } else {
        // 插入訂單
        $stmt_order = mysqli_prepare($link, "INSERT INTO orders (user_id, total_price) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt_order, 'id', $user_id, $total_price);
        mysqli_stmt_execute($stmt_order);
        $order_id = mysqli_insert_id($link);
        mysqli_stmt_close($stmt_order);

        // 插入訂單詳情並更新庫存
        foreach ($cart_items as $item) {
            $stmt_order_details = mysqli_prepare($link, "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_order_details, 'iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
            mysqli_stmt_execute($stmt_order_details);
            mysqli_stmt_close($stmt_order_details);

            $stmt_update_stock = mysqli_prepare($link, "UPDATE products SET stock = stock - ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update_stock, 'ii', $item['quantity'], $item['product_id']);
            mysqli_stmt_execute($stmt_update_stock);
            mysqli_stmt_close($stmt_update_stock);
        }

        // 清空購物車
        $stmt_clear_cart = mysqli_prepare($link, "DELETE FROM cart WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt_clear_cart, 'i', $user_id);
        mysqli_stmt_execute($stmt_clear_cart);
        mysqli_stmt_close($stmt_clear_cart);

        header("Location: order_confirmation.php?total_price=$total_price");
        exit;
    }
}

// 獲取購物車中的產品
$user_id = $_SESSION['username'];
// 獲取用戶ID
$stmt_user = mysqli_prepare($link, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt_user, 's', $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$row_user = mysqli_fetch_assoc($result_user);
$user_id = $row_user['id'];
mysqli_stmt_close($stmt_user);

$stmt_cart = mysqli_prepare($link, "SELECT cart.id as cart_id, products.id as product_id, products.name, products.price, cart.quantity FROM cart JOIN products ON cart.product_id = products.id WHERE cart.user_id = ?");
mysqli_stmt_bind_param($stmt_cart, 'i', $user_id);
mysqli_stmt_execute($stmt_cart);
$result_cart = mysqli_stmt_get_result($stmt_cart);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>購物車</title>
</head>
<body>
    <h1>購物車</h1>
    <?php echo $message; ?>
    <form action="cart.php" method="post">
        <table border="1">
            <tr>
                <th>ID</th>
                <th>名稱</th>
                <th>價格</th>
                <th>數量</th>
                <th>總價</th>
                <th>操作</th>
            </tr>
            <?php
            $total_price = 0;
            while ($row = mysqli_fetch_assoc($result_cart)):
                $total_price += $row['price'] * $row['quantity'];
            ?>
            <tr>
                <td><?php echo $row['product_id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['price']; ?></td>
                <td><input type="number" name="quantity" value="<?php echo $row['quantity']; ?>" min="1"></td>
                <td><?php echo $row['price'] * $row['quantity']; ?></td>
                <td>
                    <input type="hidden" name="cart_id" value="<?php echo $row['cart_id']; ?>">
                    <input type="submit" name="update_cart" value="更新數量">
                    <input type="submit" name="delete_from_cart" value="刪除">
                </td>
            </tr>
            <?php endwhile; ?>
            <tr>
                <td colspan="4">總價</td>
                <td colspan="2"><?php echo $total_price; ?></td>
            </tr>
        </table>
        <input type="submit" name="checkout" value="下訂">
    </form>
    <a href="user.php">繼續添加產品</a>
</body>
</html>

<?php
mysqli_free_result($result_cart);
mysqli_close($link);
?>

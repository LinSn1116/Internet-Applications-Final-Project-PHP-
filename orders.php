<?php
session_start();
error_reporting(0);
if (!isset($_SESSION['login']) || $_SESSION['login'] != 1) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    die('Error: Invalid user ID');
}

$link = @mysqli_connect('localhost', 'root', '', 'ecommerce');

if (!$link) {
    die('Error: ' . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];
$message = "";

// 處理取消訂單請求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_order_id'])) {
    $order_id = $_POST['cancel_order_id'];

    // 確認訂單屬於當前用戶且狀態為 準備中
    $stmt_check = mysqli_prepare($link, "SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = '準備中'");
    mysqli_stmt_bind_param($stmt_check, 'ii', $order_id, $user_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) > 0) {
        $stmt_order_details = mysqli_prepare($link, "SELECT * FROM order_details WHERE order_id = ?");
        mysqli_stmt_bind_param($stmt_order_details, 'i', $order_id);
        mysqli_stmt_execute($stmt_order_details);
        $result_order_details = mysqli_stmt_get_result($stmt_order_details);

        while ($order_detail = mysqli_fetch_assoc($result_order_details)) {
            $product_id = $order_detail['product_id'];
            $quantity = $order_detail['quantity'];

            // 將產品數量加回庫存
            $stmt_update_stock = mysqli_prepare($link, "UPDATE products SET stock = stock + ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update_stock, 'ii', $quantity, $product_id);
            mysqli_stmt_execute($stmt_update_stock);
            mysqli_stmt_close($stmt_update_stock);
        }
        mysqli_stmt_close($stmt_order_details);

        // 更新訂單狀態為 已取消
        $stmt_cancel = mysqli_prepare($link, "UPDATE orders SET status = '已取消' WHERE id = ?");
        mysqli_stmt_bind_param($stmt_cancel, 'i', $order_id);
        if (mysqli_stmt_execute($stmt_cancel)) {
            $message = "<font color='green'>訂單已成功取消。</font>";
        } else {
            $message = "<font color='red'>無法取消訂單，請再試一次。</font>";
        }
        mysqli_stmt_close($stmt_cancel);
    } else {
        $message = "<font color='red'>訂單未找到或無法取消該訂單。</font>";
    }
    mysqli_stmt_close($stmt_check);
}

// 獲取使用者的訂單
$stmt_orders = mysqli_prepare($link, "SELECT * FROM orders WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_orders, 'i', $user_id);
mysqli_stmt_execute($stmt_orders);
$result_orders = mysqli_stmt_get_result($stmt_orders);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的訂單</title>
</head>
<body>
    <h1>我的訂單</h1>
    <a href="user.php">返回首頁</a>
    <h2>訂單列表</h2>
    <?php echo $message; ?>
    <table border="1">
        <tr>
            <th>訂單ID</th>
            <th>總價</th>
            <th>狀態</th>
            <th>創建時間</th>
            <th>明細</th>
            <th>操作</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result_orders)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['total_price']; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td><?php echo $row['created_at']; ?></td>
            <td>
                <table border="1">
                    <tr>
                        <th>名稱</th>
                        <th>數量</th>
                        <th>價格</th>
                    </tr>
                    <?php
                    $stmt_order_details = mysqli_prepare($link, "SELECT order_details.product_id, products.name, order_details.quantity, order_details.price FROM order_details JOIN products ON order_details.product_id = products.id WHERE order_details.order_id = ?");
                    mysqli_stmt_bind_param($stmt_order_details, 'i', $row['id']);
                    mysqli_stmt_execute($stmt_order_details);
                    $result_order_details = mysqli_stmt_get_result($stmt_order_details);

                    while ($order_detail = mysqli_fetch_assoc($result_order_details)): ?>
                    <tr>
                        <td><?php echo $order_detail['name']; ?></td>
                        <td><?php echo $order_detail['quantity']; ?></td>
                        <td><?php echo $order_detail['price']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php mysqli_stmt_close($stmt_order_details); ?>
                </table>
            </td>
            <td>
                <?php if ($row['status'] == '準備中'): ?>
                <form method="POST" action="orders.php">
                    <input type="hidden" name="cancel_order_id" value="<?php echo $row['id']; ?>">
                    <button type="submit">取消訂單</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php
mysqli_free_result($result_orders);
mysqli_close($link);

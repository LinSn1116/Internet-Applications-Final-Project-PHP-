<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] != 1 || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

$link = @mysqli_connect('localhost', 'root', '', 'ecommerce');

if (!$link) {
    die('Error: ' . mysqli_connect_error());
}

$message = "";
$orders_message = "";

// 處理完成訂單請求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_order_id'])) {
    $complete_order_id = $_POST['complete_order_id'];

    $stmt_complete_order = mysqli_prepare($link, "UPDATE orders SET status = '已完成' WHERE id = ?");
    mysqli_stmt_bind_param($stmt_complete_order, 'i', $complete_order_id);
    if (mysqli_stmt_execute($stmt_complete_order)) {
        $orders_message .= "<font color='green'>訂單已完成。</font>";
    } else {
        $orders_message .= "<font color='red'>無法完成訂單，請再試一次。</font>";
    }
    mysqli_stmt_close($stmt_complete_order);
}

// 處理配送中訂單請求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['shipping_order_id'])) {
    $shipping_order_id = $_POST['shipping_order_id'];

    $stmt_shipping_order = mysqli_prepare($link, "UPDATE orders SET status = '配送中' WHERE id = ?");
    mysqli_stmt_bind_param($stmt_shipping_order, 'i', $shipping_order_id);
    if (mysqli_stmt_execute($stmt_shipping_order)) {
        $orders_message .= "<font color='green'>訂單已標記為配送中。</font>";
    } else {
        $orders_message .= "<font color='red'>無法標記訂單為配送中，請再試一次。</font>";
    }
    mysqli_stmt_close($stmt_shipping_order);
}

// 處理新增庫存請求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_stock'])) {
    foreach ($_POST['add_stock'] as $product_id => $quantity) {
        if ($quantity > 0) {
            $stmt_update_stock = mysqli_prepare($link, "UPDATE products SET stock = stock + ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update_stock, 'ii', $quantity, $product_id);
            mysqli_stmt_execute($stmt_update_stock);
            mysqli_stmt_close($stmt_update_stock);
        }
    }
    header("Location: admin.php");
    exit;
}

if (isset($_POST["username"])) {
    $username = $_POST["username"];

    $query = empty($username) ? "SELECT * FROM users" : "SELECT * FROM users WHERE username LIKE ?";
    $stmt = mysqli_prepare($link, $query);
    if (!empty($username)) {
        $username = "%" . $username . "%";
        mysqli_stmt_bind_param($stmt, 's', $username);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $message .= "<table border='1'>";
    $message .= "<tr><th>ID</th><th>Username</th><th>Login Count</th><th>Last Login</th><th>Is Admin</th><th>Order Count</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        // 查詢使用者的訂單數量
        $stmt_orders_count = mysqli_prepare($link, "SELECT COUNT(*) AS order_count FROM orders WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt_orders_count, 'i', $row['id']);
        mysqli_stmt_execute($stmt_orders_count);
        $result_orders_count = mysqli_stmt_get_result($stmt_orders_count);
        $order_row_count = mysqli_fetch_assoc($result_orders_count);
        $order_count = $order_row_count['order_count'];
        mysqli_stmt_close($stmt_orders_count);

        $message .= "<tr><td>{$row['id']}</td><td>{$row['username']}</td><td>{$row['login_count']}</td><td>{$row['last_login']}</td><td>{$row['is_admin']}</td><td>{$order_count}</td></tr>";

        // 查詢使用者的訂單明細
        $stmt_orders = mysqli_prepare($link, "SELECT * FROM orders WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt_orders, 'i', $row['id']);
        mysqli_stmt_execute($stmt_orders);
        $result_orders = mysqli_stmt_get_result($stmt_orders);

        $orders_message .= "<h3>訂單明細 (用戶ID: {$row['id']}, 用戶名: {$row['username']})</h3>";
        $orders_message .= "<table border='1'>";
        $orders_message .= "<tr><th>編號</th><th>總價</th><th>狀態</th><th>明細</th><th>創建時間</th><th>更改狀態</th></tr>";
        while ($order_row = mysqli_fetch_assoc($result_orders)) {
            $orders_message .= "<tr>";
            $orders_message .= "<td>{$order_row['id']}</td>";
            $orders_message .= "<td>{$order_row['total_price']}</td>";
            $orders_message .= "<td>{$order_row['status']}</td>";
            $orders_message .= "<td>";
            // 获取订单明细
            $stmt_order_details = mysqli_prepare($link, "SELECT order_details.product_id, products.name, order_details.quantity, order_details.price FROM order_details JOIN products ON order_details.product_id = products.id WHERE order_details.order_id = ?");
            mysqli_stmt_bind_param($stmt_order_details, 'i', $order_row['id']);
            mysqli_stmt_execute($stmt_order_details);
            $result_order_details = mysqli_stmt_get_result($stmt_order_details);
            while ($detail_row = mysqli_fetch_assoc($result_order_details)) {
                $orders_message .= "名稱: " . $detail_row['name'] . " 數量: " . $detail_row['quantity'] . " 價格: " . $detail_row['price'] . "<br>";
            }
            mysqli_stmt_close($stmt_order_details);
            $orders_message .= "</td>";
            $orders_message .= "<td>{$order_row['created_at']}</td>";
            $orders_message .= "<td>";
            // 提供修改和出貨的功能
            if ($order_row['status'] == '準備中' || $order_row['status'] == '配送中') {
                if ($order_row['status'] == '準備中') {
                    $orders_message .= "<form method='POST' action='admin.php'>";
                    $orders_message .= "<input type='hidden' name='shipping_order_id' value='{$order_row['id']}'>";
                    $orders_message .= "<input type='submit' value='配送中'>";
                    $orders_message .= "</form>";
                }
                $orders_message .= "<form method='POST' action='admin.php'>";
                $orders_message .= "<input type='hidden' name='complete_order_id' value='{$order_row['id']}'>";
                $orders_message .= "<input type='submit' value='完成訂單'>";
                $orders_message .= "</form>";
            }
            $orders_message .= "</td>";
            $orders_message .= "</tr>";
        }
        $orders_message .= "</table>";
        mysqli_stmt_close($stmt_orders);
    }
    $message .= "</table>";

    mysqli_stmt_close($stmt);
}

// 獲取所有商品
$stmt_products = mysqli_prepare($link, "SELECT * FROM products");
mysqli_stmt_execute($stmt_products);
$result_products = mysqli_stmt_get_result($stmt_products);

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員介面</title>
</head>
<body>
    <h1>管理員介面</h1>
    <form action="admin.php" method="post">
        <table>
            <tr>
                <td>使用者名稱：</td>
                <td><input type="text" name="username"></td>
            </tr>

            <tr>
                <td colspan="2"><input type="submit" value="執行"></td>
            </tr>
        </table>
    </form>
    <?php echo $message; ?>
    <?php echo $orders_message; ?>

    <h2>商品管理</h2>
    <form action="admin.php" method="post">
        <table border="1">
            <tr>
                <th>ID</th>
                <th>名稱</th>
                <th>描述</th>
                <th>價格</th>
                <th>庫存</th>
                <th>類別</th>
                <th>標籤</th>
                <th>新增庫存</th>
                <th>操作</th>
            </tr>
            <?php while ($product_row = mysqli_fetch_assoc($result_products)): ?>
            <tr>
                <td><?php echo $product_row['id']; ?></td>
                <td><?php echo $product_row['name']; ?></td>
                <td><?php echo $product_row['description']; ?></td>
                <td><?php echo $product_row['price']; ?></td>
                <td><?php echo $product_row['stock']; ?></td>
                <td><?php echo $product_row['category']; ?></td>
                <td><?php echo $product_row['tags']; ?></td>
                <td><input type="number" name="add_stock[<?php echo $product_row['id']; ?>]" min="0"></td>
                <td><input type="submit" value="更新庫存"></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </form>
    <form method="POST" action="logout.php">
        <button type="submit">登出</button>
    </form>
</body>
</html>

<?php
mysqli_free_result($result_products);
?>

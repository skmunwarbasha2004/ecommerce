<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch cart items
$stmt = $conn->prepare("SELECT cart.*, products.name, products.price FROM cart 
                        JOIN products ON cart.product_id = products.id 
                        WHERE cart.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_cost = 0;
foreach ($cart_items as &$item) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total_cost += $item['subtotal'];
}

if (empty($cart_items)) {
    echo "<script>alert('Your cart is empty!'); window.location.href='cart.php';</script>";
    exit();
}

// Order processing
$order_success = false;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['checkout'])) {
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];

    if (!empty($address)) {
        try {
            $conn->beginTransaction();

            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, address, payment_method) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $total_cost, $address, $payment_method]);
            $order_id = $conn->lastInsertId();

            // Insert order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }

            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $conn->commit();
            $order_success = true;
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Order processing failed! Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Please enter a valid shipping address.');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 40px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            font-size: 2em;
            margin-bottom: 20px;
        }
        .order-summary {
            margin-bottom: 20px;
        }
        .order-summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-summary th, .order-summary td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .total-cost {
            font-size: 1.4em;
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #218838;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #007bff;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .success-message {
            text-align: center;
            font-size: 18px;
            color: #28a745;
            font-weight: bold;
            padding: 15px;
            border: 1px solid #28a745;
            background-color: #d4edda;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Checkout</h2>

    <?php if ($order_success): ?>
        <div class="success-message">
            🎉 Payment Successful! Your Order has been Placed Successfully.<br>
            <strong>Order ID:</strong> <?= $order_id; ?><br>
            <strong>Payment Method:</strong> <?= htmlspecialchars($payment_method); ?>
        </div>
        <a href="index.php" class="back-link">Continue Shopping</a>
    <?php else: ?>

        <div class="order-summary">
            <h3>Order Summary</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']); ?></td>
                            <td><?= $item['quantity']; ?></td>
                            <td>$<?= number_format($item['price'], 2); ?></td>
                            <td>$<?= number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="total-cost">Total: $<?= number_format($total_cost, 2); ?></div>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="address">Shipping Address</label>
                <input type="text" name="address" id="address" required>
            </div>

            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="Credit Card">Credit Card</option>
                    <option value="PayPal">PayPal</option>
                    <option value="Cash on Delivery">Cash on Delivery</option>
                </select>
            </div>

            <button type="submit" name="checkout">Place Order</button>
        </form>

        <a href="cart.php" class="back-link">Back to Cart</a>
    <?php endif; ?>

</div>

</body>
</html>

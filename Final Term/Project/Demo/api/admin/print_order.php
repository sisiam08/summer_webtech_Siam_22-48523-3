<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    header('Location: ../index.html');
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Order ID is required";
    exit;
}

$orderId = $_GET['id'];

// Get order details
$orderSql = "SELECT o.*, pm.name as payment_method, 
             u.name as customer_name, u.email, u.phone,
             a.street, a.city, a.state, a.zip_code, a.country
             FROM orders o
             LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
             LEFT JOIN users u ON o.user_id = u.id
             LEFT JOIN addresses a ON o.address_id = a.id
             WHERE o.id = ?";
$orderStmt = $conn->prepare($orderSql);
$orderStmt->bind_param('i', $orderId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    echo "Order not found";
    exit;
}

$order = $orderResult->fetch_assoc();

// Get order items
$itemsSql = "SELECT oi.*, p.name as product_name 
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?";
$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->bind_param('i', $orderId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
$items = [];

while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

// Format address
$address = $order['street'] . ', ' . $order['city'] . ', ' . 
           $order['state'] . ', ' . $order['zip_code'] . ', ' . $order['country'];

// Output HTML for printing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderId; ?> - Print</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        
        .print-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .store-info {
            margin-bottom: 20px;
        }
        
        .order-info {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-box {
            border: 1px solid #ddd;
            padding: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .summary {
            display: flex;
            justify-content: flex-end;
        }
        
        .summary-table {
            width: 300px;
        }
        
        .summary-table td {
            padding: 5px 10px;
        }
        
        .total-row {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 0.9em;
            color: #666;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <div>
            <h1>Order Invoice</h1>
            <p>Order #<?php echo $orderId; ?></p>
        </div>
        <div class="store-info">
            <h2>Online Grocery Store</h2>
            <p>123 Market Street, City Name</p>
            <p>Phone: (123) 456-7890</p>
            <p>Email: info@grocerystore.com</p>
        </div>
    </div>
    
    <div class="order-info">
        <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
        <p><strong>Order Status:</strong> <?php echo $order['status']; ?></p>
        <p><strong>Payment Method:</strong> <?php echo $order['payment_method']; ?></p>
        <p><strong>Payment Status:</strong> <?php echo $order['payment_status']; ?></p>
    </div>
    
    <div class="info-grid">
        <div class="info-box">
            <h3>Bill To</h3>
            <p><strong>Name:</strong> <?php echo $order['customer_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
            <p><strong>Phone:</strong> <?php echo $order['phone'] ?? 'N/A'; ?></p>
        </div>
        <div class="info-box">
            <h3>Ship To</h3>
            <p><?php echo $address; ?></p>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo $item['product_name']; ?> <?php echo $item['variant'] ? '(' . $item['variant'] . ')' : ''; ?></td>
                <td>$<?php echo number_format($item['price'], 2); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="summary">
        <table class="summary-table">
            <tr>
                <td>Subtotal:</td>
                <td>$<?php echo number_format($order['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td>Shipping:</td>
                <td>$<?php echo number_format($order['shipping_fee'], 2); ?></td>
            </tr>
            <tr>
                <td>Tax:</td>
                <td>$<?php echo number_format($order['tax'], 2); ?></td>
            </tr>
            <tr class="total-row">
                <td>Total:</td>
                <td>$<?php echo number_format($order['total'], 2); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <p>Thank you for shopping with us!</p>
        <p>If you have any questions about this invoice, please contact our customer support.</p>
    </div>
    
    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()">Print Invoice</button>
        <button onclick="window.close()">Close</button>
    </div>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>

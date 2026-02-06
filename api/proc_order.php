<?php
// api/proc_order.php
session_start();
// Check request method is POST (Stop direct access)
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}
require_once __DIR__ . '/../includes/dbconnection.php';
$pdo = getDatabaseConnection();

$buyerID = $_SESSION['user_id'];
$productIDs = $_POST['product_ids'] ?? []; // Get array of product_id(s)

if (empty($productIDs)) {
    header("Location: ../index.php?msg=empty_order");
    exit;
}

try {
    // Start the Transaction - ACID
    $pdo->beginTransaction();
    // Check delivery address exists (required)
    if (!$addressId = $_SESSION['user_address_id'] ?? null) throw new Exception("No delivery address found for this account.");
    
    // Race Condition Protection protection using FOR UPDATE to 'lock' the rows in Database
    $placeholders = implode(',', array_fill(0, count($productIDs), '?'));
    $checkStmt = $pdo->prepare(
        "SELECT product_id FROM products 
                 WHERE product_id IN ($placeholders) 
                 AND status_id = 1 
                 FOR UPDATE");
    $checkStmt->execute($productIDs);
    $availableItems = $checkStmt->fetchAll(PDO::FETCH_COLUMN); //Only list required, not an array
    
    // Check if all requested items available
    if (count($availableItems) !== count($productIDs)) {
        throw new Exception("One or more items are no longer available. Someone may have just purchased them!");
    }
    
    // Calculate Total & Create Order --
    $priceTotalStmt = $pdo->prepare(
        "SELECT SUM(price) as total 
        FROM products 
        WHERE product_id IN ($placeholders)");
    $priceTotalStmt->execute($productIDs);
    $totalAmount = $priceTotalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // VAT (Rounded to 2 decimal places for database stability)
    $tax = round($totalAmount - ($totalAmount / 1.2), 2); 
    
    // Record Order in database
    $sqlOrder = "INSERT INTO orders (user_id, address_id, status_code_id, total_amount, tax_amount) 
                 VALUES (?, ?, 1, ?, ?)";
    $stmtOrder = $pdo->prepare($sqlOrder);
    $stmtOrder->execute([$buyerID, $addressId, $totalAmount, $tax]);
    $orderID = $pdo->lastInsertId();

    // Prepare PDO statements for data entry
    $itemPrice = $pdo->prepare(
        "SELECT price 
        FROM products 
        WHERE product_id = ?");
    $purchase = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, quantity, price_each)
        VALUES (?, ?, 1, ?)");
    $status = $pdo->prepare(
        "UPDATE products 
        SET status_id = 3 
        WHERE product_id = ?");
    // Insert the data    
    foreach ($productIDs as $id) {
        $itemPrice->execute([$id]);
        $price =$itemPrice->fetchColumn();

        $purchase->execute([$orderID, $id, $price]);
        $status->execute([$id]);
    }
    // Execute the Transaction - ACID
    $pdo->commit();
    // Clear the basket, purchase is complete
    $_SESSION['basket'] = [];
    header("Location: ../dashboard.php?msg=purchase_complete");
    exit;
} 
catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h1>Transaction Failed!</h1>";
    echo "<p>Failure occurred on line: " . $e->getLine() . "</p>";
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>";
    print_r($_SESSION['basket'] ?? 'Basket is empty');
    echo "</pre>";
    echo "<a href='../basket.php'>Return to Basket</a>";
    exit; 
}
<?php
// api/proc_order.php
session_start();
require_once __DIR__ . '/../includes/dbconnection.php';

// 1. Security Check: Must be logged in and coming from a POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

$pdo = getDatabaseConnection();
$buyerID = $_SESSION['user_id'];
$productIDs = $_POST['product_ids'] ?? [];

if (empty($productIDs)) {
    header("Location: ../index.php?msg=empty_order");
    exit;
}

try {
    // Start the Transaction - ACID
    $pdo->beginTransaction();

    // -- ATOMIC CHECK: Race Condition Protection --
    $placeholders = implode(',', array_fill(0, count($productIDs), '?'));
    $checkSql = "SELECT product_id FROM products 
                 WHERE product_id IN ($placeholders) 
                 AND status_id = 1 
                 FOR UPDATE";
    
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($productIDs);
    $availableItems = $checkStmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($availableItems) !== count($productIDs)) {
        throw new Exception("One or more items are no longer available. Someone may have just purchased them!");
    }

    // -- A. Calculate Total & Create Order --
    $priceStmt = $pdo->prepare("SELECT SUM(price) as total FROM products WHERE product_id IN ($placeholders)");
    $priceStmt->execute($productIDs);
    $totalAmount = $priceStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // VAT Logic: Inclusive of 20%. 
    // We calculate the tax portion already inside the total for record-keeping.
    $tax = $totalAmount - ($totalAmount / 1.2); 

    // Create the Main Order Record
    // (Using mockup values for address_id = 1 and status_code_id = 1 for 'Pending')
    $sqlOrder = "INSERT INTO orders (user_id, address_id, status_code_id, total_amount, tax_amount) 
                 VALUES (?, 1, 1, ?, ?)";
    // ################################################################################################################
    // ########## HARDCODED ^^ ADDRESS ID - DONT FORGET TO FIX IT #####################################################             
    // ################################################################################################################
    
    $stmtOrder = $pdo->prepare($sqlOrder);
    $stmtOrder->execute([$buyerID, $totalAmount, $tax]);
    
    $orderID = $pdo->lastInsertId();

    // -- B. Process each item --
    $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, 1, ?)";
    $stmtItem = $pdo->prepare($sqlItem);

    // Update Product Status to 'Sold' (Status ID 3)
    $sqlStatus = "UPDATE products SET status_id = 3 WHERE product_id = ?";
    $stmtStatus = $pdo->prepare($sqlStatus);

    foreach ($productIDs as $id) {
        $pStmt = $pdo->prepare("SELECT price FROM products WHERE product_id = ?");
        $pStmt->execute([$id]);
        $price = $pStmt->fetchColumn();

        $stmtItem->execute([$orderID, $id, $price]);
        $stmtStatus->execute([$id]);
    }

    // -- C. Finalise --
    $pdo->commit();

    // Clear the basket since the purchase is complete
    $_SESSION['basket'] = [];

    header("Location: ../dashboard.php?msg=purchase_complete");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h1>Transaction Failed!</h1>";
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>";
    print_r($_SESSION['basket'] ?? 'Basket is empty');
    echo "</pre>";
    echo "<a href='../basket.php'>Return to Basket</a>";
    exit; 
}
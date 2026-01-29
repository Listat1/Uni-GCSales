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

    // -- A. Calculate Total & Create Order --
    // In a real system, you'd re-verify prices from the DB here
    $placeholders = implode(',', array_fill(0, count($productIDs), '?'));
    $priceStmt = $pdo->prepare("SELECT SUM(price) as total FROM products WHERE product_id IN ($placeholders)");
    $priceStmt->execute($productIDs);
    $totalAmount = $priceStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Create the Main Order Record
    // (Using mockup values for address_id = 1 and status_code_id = 1 for 'Pending')
    $sqlOrder = "INSERT INTO orders (user_id, address_id, status_code_id, total_amount, tax_amount) 
                 VALUES (?, 1, 1, ?, ?)";
    // ################################################################################################################
    // ########## HARDCODED ^^ ADDRESS ID - DONT FORGET TO FIX IT #####################################################             
    // ################################################################################################################
    $tax = $totalAmount * 0.20; // Example 20% Tax logic
    $stmtOrder = $pdo->prepare($sqlOrder);
    $stmtOrder->execute([$buyerID, $totalAmount, $tax]);
    
    $orderID = $pdo->lastInsertId();

    // -- B. Process each item --
    $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, 1, ?)";
    $stmtItem = $pdo->prepare($sqlItem);

    // Update Product Status to 'Sold' (Status ID 3 in your system)
    $sqlStatus = "UPDATE products SET status_id = 3 WHERE product_id = ?";
    $stmtStatus = $pdo->prepare($sqlStatus);

    foreach ($productIDs as $id) {
        // Get individual price for the order_items record
        $pStmt = $pdo->prepare("SELECT price FROM products WHERE product_id = ?");
        $pStmt->execute([$id]);
        $price = $pStmt->fetchColumn();

        // 1. Link to Order
        $stmtItem->execute([$orderID, $id, $price]);
        
        // 2. Mark as Sold
        $stmtStatus->execute([$id]);
    }

    // -- C. Finalise --
    $pdo->commit();

    // Clear the basket since the purchase is complete
    $_SESSION['basket'] = [];

    header("Location: ../dashboard.php?msg=purchase_complete");
    exit;

// } catch (Exception $e) {
//     // Something went wrong, roll back the database to how it was
//     $pdo->rollBack();
//     header("Location: ../checkout.php?msg=order_error");
//     exit;
// }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // DISPLAY DEBUG MESSSAGE ON SCREEN
    echo "<h1>Transaction Failed!</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<pre>";
    print_r($_SESSION['basket'] ?? 'Basket is empty');
    echo "</pre>";
    exit; 
}
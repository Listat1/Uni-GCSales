<?php
ini_set('memory_limit', '256M');// NS_ERROR_NET_ERROR_RESPONSE
// api/proc_add_product.php
session_start();
// Check request method is POST (Stop direct access)
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit;
}
// --- System Integrity Check ---
require_once __DIR__ . '/../includes/dbconnection.php';
$pdo = getDatabaseConnection();
// Collate and Sanitise Data
$cat_id      = (int)$_POST['category_id'];
$seller_id   = $_SESSION['user_id'];
$status_id   = (int)$_POST['status_id'];
$name        = trim($_POST['name']);
$short_desc  = trim($_POST['description']);
$price       = (float)$_POST['price'];
$stock       = (int)$_POST['stock_quantity'];
// Construct Long Description content
$condition   = $_POST['condition_label'];
$user_specs  = trim($_POST['user_specs']);
$long_desc   = "CONDITION: " . $condition . "\n\nTECHNICAL DETAILS:\n" . $user_specs;
try {
    // Insert text fields into database
    $sql = "INSERT INTO products 
            (category_id, seller_id, status_id, name, description, long_description, price, stock_quantity) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cat_id, $seller_id, $status_id, $name, $short_desc, $long_desc, $price, $stock]);

    // Add images to new entry
    $productID = $pdo->lastInsertId();
    require_once __DIR__ . '/../includes/ImageProcessor.php';
    processAndStoreImages($pdo, $productID, $name);

    header("Location: ../dashboard.php?msg=item_added");
    exit;

} catch (PDOException $e) {
    header("Location: ../add_product.php?msg=error");
    exit;
}
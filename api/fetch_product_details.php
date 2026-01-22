<?php
// api/fetch_product_details.php
require_once __DIR__ . '/../includes/dbconnection.php';
header('Content-Type: application/json');

// Get ID from the JS fetch request (When user selects "details")
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(['error' => 'Invalid or missing Product ID']);
    exit;
}

$db = getDatabaseConnection();

// Only fetch the heavy 'long_description' when specifically requested
$stmt = $db->prepare("SELECT name, long_description FROM products WHERE product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    // 
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}
// Fetch all images for this product and place in array (for carousel)
$imgStmt = $db->prepare("SELECT file_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
$imgStmt->execute([$id]);
$product['images'] = $imgStmt->fetchAll(PDO::FETCH_COLUMN); 
// Display array in a format recognised by JS 
echo json_encode($product);
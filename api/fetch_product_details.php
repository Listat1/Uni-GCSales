<?php
// api/fetch_product_details.php
// Fetches full description of product when user selects more info via JS on index.php
// Session check is not performed here as visitors must be able to browse, not just registered members.
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/dbconnection.php';

// Sanitise ID from the JS fetch request (When user selects "details")
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    echo json_encode(['error' => 'Invalid or missing Product ID']);
    exit;
}
$db = getDatabaseConnection();
$stmt = $db->prepare(
    "SELECT name, price, long_description 
    FROM products 
    WHERE product_id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    // 
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}
// If (long) description provided then ? long_description : default_message
if (empty(trim($product['long_description']))) {
    $product['long_description'] = '<em class="text-muted">No further details available for this item.</em>';
}
// Fetch all images for this product and place in array (for carousel)
$imgStmt = $db->prepare(
    "SELECT file_path
    FROM product_images
    WHERE product_id = ? 
    ORDER BY sort_order ASC");
$imgStmt->execute([$id]);
$product['images'] = $imgStmt->fetchAll(PDO::FETCH_COLUMN); 
// Display array in a format recognised by JS 
echo json_encode($product);
<?php
// api/fetch_product_list.php
// Session check is not performed here as visitors must be able to browse, not just registered members.
require_once __DIR__ . '/../includes/dbconnection.php';
require_once __DIR__ . '/../includes/db_select.php';
require_once __DIR__ . '/../includes/components.php'; 
$db = getDatabaseConnection();
$q = $_GET['q'] ?? '';
$cat = $_GET['cat'] ?? '';
$products = searchProducts($db, $q, $cat);
// Map the HTML into the result array
foreach ($products as &$p) {
    // Ensure long_description exists for the initial collapse state text
    $p['long_description'] = 'No further details available.'; 
    $p['card_html'] = renderProductCard($p);
}
header('Content-Type: application/json');
echo json_encode($products);
<?php
// api/fetch_product_list.php
require_once __DIR__ . '/../includes/dbconnection.php';
require_once __DIR__ . '/../includes/db_select.php';

$db = getDatabaseConnection();

// One line to get the data using the shared function
$products = searchProducts($db, $_GET['q'] ?? '', $_GET['cat'] ?? '');

header('Content-Type: application/json');
echo json_encode($products);
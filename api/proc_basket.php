<?php
// api/proc_basket.php
session_start();
// Check request is POST and product_id exists
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'])) {
    header("Location: ../index.php");
    exit;
}
$productID = (int)$_POST['product_id'];
// Start Basket if not already started
if (!isset($_SESSION['basket'])) {
    $_SESSION['basket'] = [];
}
// Add Item to basket
if (!in_array($productID, $_SESSION['basket'])) {
    $_SESSION['basket'][] = $productID;
}

// Redirect back to the referring page (Index or Product details)
$referrer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
$separator = (parse_url($referrer, PHP_URL_QUERY)) ? '&' : '?';

header("Location: " . $referrer . $separator . "msg=added_to_basket");
exit;
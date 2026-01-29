<?php
// api/proc_basket.php
session_start();

// Ensure we have a product ID and a valid POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'])) {
    header("Location: ../index.php");
    exit;
}

$productID = (int)$_POST['product_id'];

// Initialise basket session if it doesn't exist
if (!isset($_SESSION['basket'])) {
    $_SESSION['basket'] = [];
}

// -- Add Item Logic --
// We use the ID as the Key. Since these are unique items, 
// we don't need 'quantities', just a simple true/false presence.
if (!in_array($productID, $_SESSION['basket'])) {
    $_SESSION['basket'][] = $productID;
}

// Redirect back to the referring page (Index or Product details)
// We add a 'msg' so the UI can show a "Success" toast/alert if you want.
$referrer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
$separator = (parse_url($referrer, PHP_URL_QUERY)) ? '&' : '?';

header("Location: " . $referrer . $separator . "msg=added_to_basket");
exit;
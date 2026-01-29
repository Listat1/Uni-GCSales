<?php
// api/proc_edit_product.php
session_start();
require_once __DIR__ . '/../includes/dbconnection.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit;
}

$pdo = getDatabaseConnection();
$userID = $_SESSION['user_id'];
$productID = (int)$_POST['product_id'];

// Check ownership of requested record to edit
$check = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ? AND seller_id = ?");
$check->execute([$productID, $userID]);
if (!$check->fetch()) {
    die("Unauthorised action.");
}

// Collate and Sanitise POSTED Data 
$name           = trim($_POST['name']);
$short_desc     = trim($_POST['description']);
$price          = (float)$_POST['price'];
$stock          = (int)$_POST['stock_quantity'];
$status_id      = (int)$_POST['status_id'];
$cat_id         = (int)$_POST['category_id'];
$condition      = $_POST['condition_label'];
$product_specs  = trim($_POST['product_specs']);
$long_desc      = "CONDITION: " . $condition . "\n\nTECHNICAL DETAILS:\n" . $product_specs;

try {
    // -- Update text fields into database --
    $sql = "UPDATE products 
        SET name = ?, 
            description = ?,
            price = ?,
            stock_quantity = ?,
            status_id = ?,
            category_id = ?,
            long_description = ?
        WHERE product_id = ? 
        AND seller_id = ?";
    $pdo->prepare($sql)->execute([
        $name, $short_desc, $price, $stock, 
        $status_id, $cat_id, $long_desc, $productID, $userID
    ]);

    // -- Replace Images if supplied --
    if (!empty($_FILES['product_images']['name'][0])) {
        // Get all file pointers for the images
        $curFilesStmt = $pdo->prepare("SELECT file_path, thumb_path FROM product_images WHERE product_id = ?");
        $curFilesStmt->execute([$productID]);
        $fileList = $curFilesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Delete the files from disk: Check NULL / Existence / Placeholder
        foreach ($fileList as $file) {
            // Full Sized Images
            if (!empty($file['file_path']) && 
                is_file($path = "../" . $file['file_path']) && 
                strpos($path, 'placeholder') === false) {
                    unlink($path);
            }
            // Thumbnails
            if (!empty($file['thumb_path']) && 
                is_file($path = "../" . $file['thumb_path']) && 
                strpos($path, 'placeholder') === false) {
                    unlink($path);
            }
        }

        // Delete references from the Database
        $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$productID]);

        // Add images to entry
        require_once __DIR__ . '/../includes/ImageProcessor.php';
        processAndStoreImages($pdo, $productID, $name);
    }

    header("Location: ../dashboard.php?msg=updated");
    exit;

} catch (PDOException $e) {
    header("Location: ../edit_product.php?id=$productID&msg=error");
    exit;
}
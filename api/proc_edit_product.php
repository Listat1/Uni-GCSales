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

// Get product ownership and status
$stmt = $pdo->prepare("SELECT product_id, status_id
    FROM products
    WHERE product_id = ? AND seller_id = ?
");
$stmt->execute([$productID, $userID]);
// Check Ownership/status
$checkProduct = $stmt->fetch();
if (!$checkProduct) {
    // Product does not exist / belong to this user
    die("Unauthorised action.");
} elseif ((int)$checkProduct['status_id'] === 3) {
    // Product is sold (status = 3), cannot edit
    header("Location: ../dashboard.php?msg=locked");
    exit;
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
        AND seller_id = ?
        AND status_id != 3"; // << Updates to items with status "sold" (3) will fail
    $pdo->prepare($sql)->execute([
        $name, $short_desc, $price, $stock, 
        $status_id, $cat_id, $long_desc, $productID, $userID
    ]);

    // -- Replace Images if supplied --
    if (!empty($_FILES['product_images']['name'][0])) {
        // Get all file pointers for the images
        $stmt = $pdo->prepare("SELECT file_path, thumb_path FROM product_images WHERE product_id = ?");
        $stmt->execute([$productID]);
        $fileList = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    // Because AND status_id != 3"; prevents a write, instead of allowing the attempt, no exception will be raised
    // Unless there is a genuine PDO error. Before redirecting back to dashboard, need to check the actual update state.

    header("Location: ../dashboard.php?msg=updated&item=Product");
    exit;

} catch (PDOException $e) {
    header("Location: ../edit_product.php?id=$productID&msg=error");
    exit;
}
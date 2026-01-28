<?php
// api/proc_add_product.php
session_start();
// Check request method is POST (Stop direct access)
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit;
}
// --- System Integrity Check ---
// Assign function tested to re-usable variable
$canWebp = function_exists(function: 'imagewebp') && function_exists('imagecreatefromwebp');
if ($canWebp) {
    $gdSaveMethod = 'imagewebp';
    $ext      = '.webp';
} else {
    $gdSaveMethod = 'imagejpeg';
    $ext      = '.jpg';
}
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
    // -- Insert text fields into database --
    $sql = "INSERT INTO products 
            (category_id, seller_id, status_id, name, description, long_description, price, stock_quantity) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cat_id, $seller_id, $status_id, $name, $short_desc, $long_desc, $price, $stock]);

    // -- Save Uploaded Image & place location pointer in database --
    $productID = $pdo->lastInsertId();
    $imgPrefix = str_pad($productID, 5, "0", STR_PAD_LEFT);
    if (!empty($_FILES['product_images']['name'][0])) {
        $imgDir = "../assets/graphics/products/";
        $imgCount = min(count($_FILES['product_images']['name']), 5);
        $finfo = new finfo(FILEINFO_MIME_TYPE);

    for ($i = 0; $i < $imgCount; $i++) {
        $imgPath = $_FILES['product_images']['tmp_name'][$i];
        
        // Check the image is really an image
        $mimeType = $finfo->file($imgPath);
        // Assign Data to Source Image $srcImage
        switch ($mimeType) {
            case 'image/jpeg': $srcImage = imagecreatefromjpeg($imgPath); break;
            case 'image/png':  $srcImage = imagecreatefrompng($imgPath);  break;
            case 'image/webp': $srcImage = imagecreatefromwebp($imgPath); break;
            default: continue 2; 
            }
            $fileNum = $i + 1;
            // Get Current ImageSize 
            $origW = imagesx($srcImage);
            $origH = imagesy($srcImage);
            // Process Widescreen Main Image (800x450)
            $imgFileName = "{$imgPrefix}_{$fileNum}{$ext}";
            $image = imagecreatetruecolor(800, 450);
            // Background fill and center-crop math
            $white = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $white);
            $ratio = max(800 / $origW, 450 / $origH);
            $cropW = 800 / $ratio;
            $cropH = 450 / $ratio;
            $offsetX = ($origW - $cropW) / 2;
            $offsetY = ($origH - $cropH) / 2;
            // Resize
            imagecopyresampled($image, $srcImage, 0, 0, $offsetX, $offsetY, 800, 450, $cropW, $cropH);
            // Execution: No 'if' needed, we use the function name stored in $gdSaveMethod
            $gdSaveMethod($image, $imgDir . $imgFileName, 85);
            imagedestroy($image);

            // Create 200x200 Square Thumbnail (First image only)
            $thumbFileName = null;
            if ($fileNum === 1) {
                $thumbFileName = "{$imgPrefix}_thumb{$ext}";
                $thumbImg = imagecreatetruecolor(200, 200);
                
                $size = min($origW, $origH);
                $tOffX = ($origW - $size) / 2;
                $tOffY = ($origH - $size) / 2;
                
                imagecopyresampled($thumbImg, $srcImage, 0, 0, $tOffX, $tOffY, 200, 200, $size, $size);
                
                $gdSaveMethod($thumbImg, $imgDir . $thumbFileName, 80);
                imagedestroy($thumbImg);
            }
            imagedestroy($srcImage);
            // Update Database image links
            $dbPath = "assets/graphics/products/" . $imgFileName;
            $dbThumb = $thumbFileName ? "assets/graphics/products/" . $thumbFileName : null;
            $imgSql = "INSERT INTO product_images (product_id, file_path, thumb_path, alt_text, sort_order) 
                       VALUES (?, ?, ?, ?, ?)";
            $imgStmt = $pdo->prepare($imgSql);
            $imgStmt->execute([$productID, $dbPath, $dbThumb, $name . " View " . $fileNum, $fileNum]);
        }
    }
    else {
        // -- No Images Supplied: Use placeholders --
        $dbPath   = "assets/graphics/products/placeholder.webp";
        $dbThumb  = "assets/graphics/products/placeholder_thumb.webp";
        $altText  = "No Image provided for " . $name;
        $sortOrder = 1; 
        $imgSql = "INSERT INTO product_images (product_id, file_path, thumb_path, alt_text, sort_order) 
                   VALUES (?, ?, ?, ?, ?)";
        $imgStmt = $pdo->prepare($imgSql);
        $imgStmt->execute([$productID, $dbPath, $dbThumb, $altText, $sortOrder]);
    }

    header("Location: ../dashboard.php?msg=item_added");
    exit;

} catch (PDOException $e) {
    header("Location: ../add_product.php?msg=error");
    exit;
}
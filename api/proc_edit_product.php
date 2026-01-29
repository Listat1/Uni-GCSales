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
$productID      = (int)$_POST['product_id'];
$userID         = $_SESSION['user_id'];

try {
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
    $pdo->prepare($sql)
        ->execute([$name, $short_desc, $price, $stock, 
            $status_id, $cat_id, $long_desc, $productID, $userID
        ]
    );
    // Replace Images if supplied
    if (!empty($_FILES['product_images']['name'][0])) {
        // Get all file pointers for the images
        $curFilesStmt = $pdo->prepare("SELECT file_path, thumb_path 
            FROM product_images
            WHERE product_id = ?");
        $curFilesStmt->execute([$productID]);
        $fileList = $curFilesStmt->fetchAll(PDO::FETCH_ASSOC);
        // Delete the files from disk TESTs: NULL / EXIST on Disk / IS NOT the placeholder.
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

        // C. Setup for Processing
        $canWebp = function_exists('imagewebp') && function_exists('imagecreatefromwebp');
        $gdSaveMethod = $canWebp ? 'imagewebp' : 'imagejpeg';
        $ext = $canWebp ? '.webp' : '.jpg';
        $imgDir = "../assets/graphics/products/";
        $imgPrefix = str_pad($productID, 5, "0", STR_PAD_LEFT);
        $imgCount = min(count($_FILES['product_images']['name']), 5);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        
        $firstThumbPath = null; // To store the path for Row 1

        // D. The Loop (Process the new images)
        for ($i = 0; $i < $imgCount; $i++) {
            $imgPath = $_FILES['product_images']['tmp_name'][$i];
            $mimeType = $finfo->file($imgPath);
            
            switch ($mimeType) {
                case 'image/jpeg': $srcImage = imagecreatefromjpeg($imgPath); break;
                case 'image/png':  $srcImage = imagecreatefrompng($imgPath);  break;
                case 'image/webp': $srcImage = imagecreatefromwebp($imgPath); break;
                default: continue 2; 
            }

            $fileNum = $i + 1;
            $origW = imagesx($srcImage);
            $origH = imagesy($srcImage);

            // Process 800x450 Main
            $imgFileName = "{$imgPrefix}_{$fileNum}{$ext}";
            $image = imagecreatetruecolor(800, 450);
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
            $ratio = max(800 / $origW, 450 / $origH);
            $cropW = 800 / $ratio; $cropH = 450 / $ratio;
            imagecopyresampled($image, $srcImage, 0, 0, ($origW - $cropW) / 2, ($origH - $cropH) / 2, 800, 450, $cropW, $cropH);
            $gdSaveMethod($image, $imgDir . $imgFileName, 85);
            imagedestroy($image);

            // Process Thumb (First image only)
            if ($fileNum === 1) {
                $thumbFileName = "{$imgPrefix}_thumb{$ext}";
                $thumbImg = imagecreatetruecolor(200, 200);
                $size = min($origW, $origH);
                imagecopyresampled($thumbImg, $srcImage, 0, 0, ($origW - $size) / 2, ($origH - $size) / 2, 200, 200, $size, $size);
                $gdSaveMethod($thumbImg, $imgDir . $thumbFileName, 80);
                imagedestroy($thumbImg);
                
                // Store the string for the DB
                $firstThumbPath = "assets/graphics/products/" . $thumbFileName;
            }
            imagedestroy($srcImage);

            // Insert new image record
            $dbPath = "assets/graphics/products/" . $imgFileName;
            // Every row now gets the first thumb path!
            $imgSql = "INSERT INTO product_images (product_id, file_path, thumb_path, alt_text, sort_order) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($imgSql)->execute([$productID, $dbPath, $firstThumbPath, $name . " View " . $fileNum, $fileNum]);
        }
    }
    header("Location: ../dashboard.php?msg=updated");
    exit;
    }
catch (PDOException $e) {
    header("Location: ../edit_product.php?id=$productID&msg=error");
    exit;
}
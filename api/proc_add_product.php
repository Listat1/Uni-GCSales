<?php
// api/proc_add_product.php
session_start();

// Check request method is POST (Stop direct access)
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit;
}

require_once __DIR__ . '/../includes/dbconnection.php';
$pdo = getDatabaseConnection();

// Collect and Sanitise
$cat_id      = (int)$_POST['category_id'];
$seller_id   = $_SESSION['user_id'];
$status_id   = (int)$_POST['status_id'];
$name        = trim($_POST['name']);
$short_desc  = trim($_POST['description']);
$price       = (float)$_POST['price'];
$stock       = (int)$_POST['stock_quantity'];

// Formulate the Long Description Template
$condition   = $_POST['condition_label'];
$user_specs  = trim($_POST['user_specs']);
$long_desc   = "CONDITION: " . $condition . "\n\nTECHNICAL DETAILS:\n" . $user_specs;

try {
    $sql = "INSERT INTO products 
            (category_id, seller_id, status_id, name, description, long_description, price, stock_quantity) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $cat_id, 
        $seller_id, 
        $status_id, 
        $name, 
        $short_desc, 
        $long_desc, 
        $price, 
        $stock
    ]);

    // -- Image Upload --
    $newID = $pdo->lastInsertId(); 
    $paddedID = str_pad($newID, 5, "0", STR_PAD_LEFT);

    if (!empty($_FILES['product_images']['name'][0])) {
        $files = $_FILES['product_images'];
        
        // Smart Check: Fallback to JPG if WebP is disabled in GD
        $useWebp = function_exists('imagewebp');
        $ext = $useWebp ? '.webp' : '.jpg';
        
        // Relative path from the /api/ folder
        $uploadDir = "../assets/graphics/products/";

        for ($i = 0; $i < min(count($files['name']), 5); $i++) {
            $tmpPath = $files['tmp_name'][$i];
            
            // 1. Create a GD resource from the uploaded file
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpPath);

            if ($mimeType === 'image/jpeg') {
                $src = imagecreatefromjpeg($tmpPath);
            } elseif ($mimeType === 'image/png') {
                $src = imagecreatefrompng($tmpPath);
            } elseif ($mimeType === 'image/webp' && function_exists('imagecreatefromwebp')) {
                $src = imagecreatefromwebp($tmpPath);
            } else {
                continue; // Skip unsupported or broken types
            }

            $fileNum = $i + 1;
            $origW = imagesx($src);
            $origH = imagesy($src);

            // 2. Process Widescreen Main Image (800x450)
            $mainFileName = "{$paddedID}_{$fileNum}{$ext}";
            $mainTarget = $uploadDir . $mainFileName;
            
            $mainImg = imagecreatetruecolor(800, 450);
            
            // Fill with white background (useful for transparent PNGs)
            $white = imagecolorallocate($mainImg, 255, 255, 255);
            imagefill($mainImg, 0, 0, $white);

            // Calculate center crop (Cover effect)
            $ratio = max(800 / $origW, 450 / $origH);
            $cropW = 800 / $ratio;
            $cropH = 450 / $ratio;
            $offsetX = ($origW - $cropW) / 2;
            $offsetY = ($origH - $cropH) / 2;

            imagecopyresampled($mainImg, $src, 0, 0, $offsetX, $offsetY, 800, 450, $cropW, $cropH);
            
            if ($useWebp) {
                imagewebp($mainImg, $mainTarget, 85);
            } else {
                imagejpeg($mainImg, $mainTarget, 85);
            }
            imagedestroy($mainImg);

            // 3. Process 200x200 Square Thumbnail ONLY for the first photo
            $thumbFileName = null;
            if ($fileNum === 1) {
                $thumbFileName = "{$paddedID}_thumb{$ext}";
                $thumbTarget = $uploadDir . $thumbFileName;
                $thumbImg = imagecreatetruecolor(200, 200);
                
                // Square Center Crop
                $size = min($origW, $origH);
                $tOffX = ($origW - $size) / 2;
                $tOffY = ($origH - $size) / 2;
                
                imagecopyresampled($thumbImg, $src, 0, 0, $tOffX, $tOffY, 200, 200, $size, $size);
                
                if ($useWebp) {
                    imagewebp($thumbImg, $thumbTarget, 80);
                } else {
                    imagejpeg($thumbImg, $thumbTarget, 80);
                }
                imagedestroy($thumbImg);
            }

            // Cleanup memory
            imagedestroy($src);

            // -- Database Bookkeeping --
            // Store relative paths starting from assets/ for frontend consistency
            $dbPath = "assets/graphics/products/" . $mainFileName;
            $dbThumb = $thumbFileName ? "assets/graphics/products/" . $thumbFileName : null;
            
            $imgSql = "INSERT INTO product_images (product_id, file_path, thumb_path, alt_text, sort_order) 
                       VALUES (?, ?, ?, ?, ?)";
            $imgStmt = $pdo->prepare($imgSql);
            $imgStmt->execute([
                $newID, 
                $dbPath, 
                $dbThumb, 
                $name . " View " . $fileNum, 
                $fileNum
            ]);
        }
    }

    header("Location: ../dashboard.php?msg=item_added");
    exit;

} catch (PDOException $e) {
    header("Location: ../add_product.php?msg=error");
    exit;
}
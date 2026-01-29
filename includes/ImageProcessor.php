<?php
/**
 * ImageProcessor: Resizes/Crops physical files and writes to Database.
 * Ensures only the 1st image is used to create a thumbnail.
 * Ensure that $_FILES['product_images'] contains valid data before using
 */
function processAndStoreImages($pdo, $productID, $name) {
    // -- System Integrity Check --
    $isWebpCapable = function_exists('imagewebp') && function_exists('imagecreatefromwebp');
    $gdSaveMethod = $isWebpCapable ? 'imagewebp' : 'imagejpeg';
    // -- Environment & Processing Methods Configuration --
    $imgDir = "../assets/graphics/products/";
    $imgPrefix = str_pad($productID, 5, "0", STR_PAD_LEFT);
    $ext = $isWebpCapable ? '.webp' : '.jpg';

    // -- Save Uploaded Image & place location pointer in database --
    if (!empty($_FILES['product_images']['name'][0])) {
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
            $dbThumb = null; 
            if ($fileNum === 1) {
                $thumbFileName = "{$imgPrefix}_thumb{$ext}";
                $thumbImg = imagecreatetruecolor(200, 200);
                
                $size = min($origW, $origH);
                $tOffX = ($origW - $size) / 2;
                $tOffY = ($origH - $size) / 2;
                
                imagecopyresampled($thumbImg, $srcImage, 0, 0, $tOffX, $tOffY, 200, 200, $size, $size);
                
                $gdSaveMethod($thumbImg, $imgDir . $thumbFileName, 80);
                imagedestroy($thumbImg);
                
                // Point to the thumb for the first record
                $dbThumb = "assets/graphics/products/" . $thumbFileName;
            }

            imagedestroy($srcImage);

            // Update Database image links
            $dbPath = "assets/graphics/products/" . $imgFileName;
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
}
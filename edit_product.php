<?php
// edit_product.php
$pageID = 'dashboard';
session_start();
require_once 'includes/dbconnection.php';
$db = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?msg=auth");
    exit;
}

$product_id = (int)$_GET['id'];
$seller_id  = $_SESSION['user_id'];

// Check ownership of requested record to edit
$stmt = $db->prepare("SELECT * FROM products WHERE product_id = ? AND seller_id = ?");
$stmt->execute([$product_id, $seller_id]);
$product = $stmt->fetch();

if (!$product) { die("Access Denied: Product not found or ownership mismatch."); }

// Reverse-Parse Long description back into Condition and Specs
$elements = explode("\n\nTECHNICAL DETAILS:\n", $product['long_description']);
$current_condition = str_replace("CONDITION: ", "", $elements[0] ?? '');
$current_specs = $elements[1] ?? '';

include 'includes/header.php';
?>

<main class="container mt-5">
    <div class="display-div p-4">
        <h2 class="mb-4">Edit Listing: <?= htmlspecialchars($product['name']) ?></h2>
        
        <form action="api/proc_edit_product.php" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">

            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Category</label>
                    <select class="form-select"
                        name="category_id" required>
                        <?php
                            $catStmt = $db->query("SELECT * FROM categories ORDER BY category_name");
                            while ($cat = $catStmt->fetch()) {
                                $selected = ($cat['category_id'] == $product['category_id']) ? 'selected' : '';
                                echo "<option value=\"{$cat['category_id']}\" $selected>" . htmlspecialchars($cat['category_name']) . "</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Product Name</label>
                    <input class="form-control"
                        name="name" 
                        type="text" 
                        value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Price (£)</label>
                    <input class="form-control"
                        name="price" 
                        type="number" 
                        step="0.01" 
                        value="<?= $product['price'] ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select"
                        name="status_id">
                        <option value="1" <?= $product['status_id'] == 1 ? 'selected' : '' ?>>Live</option>
                        <option value="3" <?= $product['status_id'] == 3 ? 'selected' : '' ?>>Sold</option>
                        <option value="2" <?= $product['status_id'] == 2 ? 'selected' : '' ?>>Hidden/Draft</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Stock Quantity</label>
                    <input class="form-control"
                        name="stock_quantity"
                        type="number" 
                        value="<?= $product['stock_quantity'] ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Condition</label>
                    <select class="form-select"
                        name="condition_label" required>
                        <?php 
                        $conditions = [
                            "New/Unused", 
                            "Refurbished (Grade A)", 
                            "Refurbished (Grade B)",
                            "Used/Good",
                            "Recycled/For Parts"
                        ];
                        foreach($conditions as $opt) {
                            $sel = ($current_condition == $opt) ? 'selected' : '';
                            echo "<option value=\"$opt\" $sel>$opt</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Short Description (Search Result Snippet)</label>
                    <input class="form-control"
                        name="description"
                        type="text" 
                        value="<?= htmlspecialchars($product['description']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Technical Specifications (Long Description)</label>
                    <textarea class="form-control"
                        name="product_specs"
                        rows="5"><?= htmlspecialchars($current_specs) ?></textarea>
                </div>

                <div class="col-12">
                    <div class="alert alert-warning border-warning mt-3">
                        <strong><i class="bi bi-exclamation-triangle"></i> Image Replacement Logic:</strong><br>
                        Uploading new files will <strong>replace</strong> all current images for this product. 
                        Leave blank to keep current images.
                    </div>
                    <label class="form-label">Upload New Images (Max 5)</label>
                    <input class="form-control"
                        name="product_images[]" 
                        type="file" multiple>
                </div>

                <div class="col-12 mt-4">
                    <button class="btn btn-primary px-5" type="submit">Save Changes</button>
                    <a class="btn btn-outline-secondary" href="dashboard.php">Discard Changes</a>
                </div>
            </div>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
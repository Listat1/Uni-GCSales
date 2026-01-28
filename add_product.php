<?php
// add_product.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?msg=auth");
    exit;
}

require_once 'includes/dbconnection.php';
$pdo = getDatabaseConnection();

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

$pageTitle = "G&C Sells - Add Listing";
$pageID = "add-product";
include 'includes/header.php';
?>

<main class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <header class="mb-4">
                <h2>Post a New Listing</h2>
                <p class="text-muted">Fill in the details for your refurbished tech.</p>
            </header>

            <form action="api/proc_add_product.php" 
                method="POST"
                enctype="multipart/form-data" 
                class="card p-4 shadow-sm bg-body-tertiary">
                
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Item Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Dell Optiplex 7050" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Price (£)</label>
                        <input type="number" name="price" step="0.01" class="form-control" placeholder="0.00" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="" selected disabled>Choose category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Condition</label>
                        <select name="condition_label" class="form-select" required>
                            <option value="New/Unused">New/Unused</option>
                            <option value="Refurbished (Grade A)">Refurbished (Grade A)</option>
                            <option value="Refurbished (Grade B)">Refurbished (Grade B)</option>
                            <option value="Used/Good">Used/Good</option>
                            <option value="Recycled/For Parts">Recycled/For Parts</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" value="1" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Listing Status</label>
                        <select name="status_id" class="form-select">
                            <option value="1">Live (Immediate)</option>
                            <option value="2">Pending (Draft)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Short Summary</label>
                        <input type="text" name="description" class="form-control" maxlength="150" placeholder="Summary for search results...">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Full Specifications & Details</label>
                        <textarea name="user_specs" class="form-control" rows="5" placeholder="Enter CPU, RAM, etc..."></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Product Photos (Max 5)</label>
                        <input type="file" name="product_images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                        <div class="form-text">First image is the main thumbnail. Widescreen works best.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="dashboard.php" class="btn btn-outline-secondary">Back</a>
                    <button type="submit" class="btn btn-success px-4">Create Listing</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
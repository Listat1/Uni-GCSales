<?php
// checkout.php
session_start();
require_once 'includes/dbconnection.php';
require_once 'includes/components.php'; 

$db = getDatabaseConnection();
$checkoutItems = [];

// Buy Now
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instant_buy'])) {
    $checkoutItems[] = (int)$_POST['product_id'];
} 
// Add to Basket
elseif (!empty($_SESSION['basket'])) {
    $checkoutItems = $_SESSION['basket'];
} 
else {
    header("Location: index.php");
    exit;
}

// Fetch Item Details
$placeholders = implode(',', array_fill(0, count($checkoutItems), '?'));
$sql = "SELECT p.*, pi.file_path 
        FROM products p 
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.sort_order = 1
        WHERE p.product_id IN ($placeholders)";
$stmt = $db->prepare($sql);
$stmt->execute($checkoutItems);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Finalise Purchase";
$pageID = "checkout";
include 'includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Review Your Order</h2>

    <div class="row">
        <div class="col-md-8">
            <?php 
            $subtotal = 0;
            foreach ($products as $p): 
                $subtotal += $p['price'];
            ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <img src="<?php $p['file_path'] ?? 'assets/graphics/products/placeholder.webp' ?>" 
                            alt="Item" class="rounded me-3" style="width: 80px; height: 60px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <h5 class="mb-0">
                            <?php htmlspecialchars($p['name']) ?>
                        </h5>
                        <small class="text-muted">Item ID: 
                            <?php str_pad($p['product_id'], 5, '0', STR_PAD_LEFT) ?>
                        </small>
                    </div>
                    <div class="text-end">
                        <span class="fs-5 fw-bold">£
                            <?php number_format($p['price'], 2) ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <a href="index.php" class="btn btn-outline-secondary mt-2">← Continue Shopping</a>
        </div>

        <div class="col-md-4">
            <div class="card shadow border-primary">
                <div class="card-header bg-primary text-white fw-bold">Order Summary</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Items:</span>
                        <span>
                            <?php count($products) ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between fs-4 fw-bold mb-4">
                        <span>Total:</span>
                        <span>£
                            <?php number_format($subtotal, 2) ?>
                        </span>
                    </div>

                    <hr>

                    <div class="mb-3 p-2 bg-light rounded border">
                        <small class="d-block text-muted mb-1">Payment Method</small>
                        <strong><i class="bi bi-credit-card"></i> Saved Card (Ending 1234)</strong>
                    </div>

                    <form action="api/proc_order.php" method="POST">
                        <?php foreach ($checkoutItems as $id): ?>
                            <input type="hidden" 
                                name="product_ids[]" 
                                value="<?php $id ?>">
                        <?php endforeach; ?>
                        
                        <button type="submit" class="btn btn-success btn-lg w-100 shadow-sm">
                            Confirm and Pay Now
                        </button>
                    </form>
                    
                    <p class="text-center small text-muted mt-3">
                        <i class="bi bi-shield-lock"></i> Secure Mockup Transaction
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
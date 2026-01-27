<?php
// dashboard.php
session_start();
// Prevent direct access to page (without login)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?msg=auth");
    exit;
}
require_once 'includes/debug_helper.php'; 
// Debug - Remove when done
release_the_hounds();
require_once 'includes/dbconnection.php';
$pdo = getDatabaseConnection();
$user_id = $_SESSION['user_id'];
try {
    // Get Profile Data
    $stmt = $pdo->prepare("SELECT * 
        FROM users 
        WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Get Listed Items
    $stmt = $pdo->prepare("SELECT * 
        FROM products 
        WHERE seller_id = ? 
        ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $myProducts = $stmt->fetchAll();

    // Get Purchase History
    $stmt = $pdo->prepare("SELECT o.order_id, o.total_amount, o.created_at 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $myPurchases = $stmt->fetchAll();
    }
catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
// Header
$pageTitle = "G&C Sells - Dashboard";
$pageID = "dashboard";
include 'includes/header.php';
?>
    <div class="hero">
        <div id="heroContent">
            <h1>Welcome to Your Dashboard</h1>
            <?php $fullName = htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
            <p style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;">
                Hello, <?php echo trim($fullName) ?: 'User'; ?>!
            </p>
            <p style="font-size: 1.1rem; opacity: 0.9; font-weight: 400;">
                Manage your account, post items for sale, and review your purchases.
            </p>
        </div>
    </div>

    <div class="display-div dashboard-scroll">
        
        <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center" 
            data-bs-toggle="collapse" 
            data-bs-target="#profileBody">
                Profile Summary <span class="collapse-icon">▼</span>
            </div>
            <div id="profileBody" class="collapse show">
                <div class="card-body">
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                    <p><strong>Level:</strong> <?php echo htmlspecialchars($user['level'] ?? '10'); ?></p>
                </div>
            </div>
        </div>

        <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center" 
            data-bs-toggle="collapse"
            data-bs-target="#postItemsBody">
                Your Listings <span class="collapse-icon">▼</span>
            </div>
            <div id="postItemsBody" class="collapse show">
                <div class="card-body">
                    <a href="add_product.php" class="btn btn-success btn-sm mb-3">Add New Product</a>
                    <div class="list-group">
                        <?php if (empty($myProducts)): ?>
                            <div class="list-group-item">No items listed.</div>
                        <?php else: foreach ($myProducts as $item): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-reset border-secondary">
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                <a href="edit_product.php?id=<?php echo $item['product_id']; ?>" class="btn btn-outline-info btn-sm">Edit</a>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center" 
            data-bs-toggle="collapse"
            data-bs-target="#historyBody">
                Purchase History <span class="collapse-icon">▼</span>
            </div>
            <div id="historyBody" class="collapse show">
                <div class="card-body">
                    <div class="list-group">
                        <?php if (empty($myPurchases)): ?>
                            <div class="list-group-item bg-transparent border-secondary text-reset">No purchases found.</div>
                        <?php else: foreach ($myPurchases as $order): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-reset border-secondary">
                                <div>
                                    <strong>Order #<?php echo $order['order_id']; ?></strong><br>
                                    <small class="opacity-75"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold d-block">£<?php echo number_format($order['total_amount'], 2); ?></span>
                                    <button class="btn btn-sm btn-outline-warning" style="font-size: 0.7rem;">Feedback</button>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
<script>
    <?php include 'assets/js/main.js'; ?>
    <?php include 'assets/js/auth.js'; ?>    
</script>
<?php 
    // Get Footer
    include 'includes/footer.php';
?>
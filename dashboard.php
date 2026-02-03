<?php
// dashboard.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?msg=auth");
    exit;
}

require_once 'includes/dbconnection.php';
$pdo = getDatabaseConnection();
$user_id = $_SESSION['user_id'];

try {
    // Get role and level of user
    $stmt = $pdo->prepare("SELECT u.*, r.role_name 
        FROM users u
        LEFT JOIN roles r ON u.level = r.auth_level
        WHERE u.user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Get items listed by user with current sold status
    $stmt = $pdo->prepare("SELECT p.*, s.status_label, u.username AS buyer_name 
        FROM products p
        JOIN product_status s ON p.status_id = s.status_id
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.order_id
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE p.seller_id = ? 
        ORDER BY p.created_at DESC");
    $stmt->execute([$user_id]);
    $myProducts = $stmt->fetchAll();

    // Get user's purchase history
    $stmt = $pdo->prepare("SELECT o.order_id, o.total_amount, o.created_at, p.name AS item_name
        FROM orders o 
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC");
    $stmt->execute([$user_id]);
    $myPurchases = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$pageTitle = "G&C Sells - Dashboard";
$pageID = "dashboard";
include 'includes/header.php';
?>

<div class="hero">
    <div id="heroContent">
        <h1>Welcome to Your Dashboard</h1>
        <?php
            $fullName = htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        ?>
        <p style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;">
            Hello, 
            <?php echo trim($fullName) ?: 'User'; ?>!
        </p>
        <p style="font-size: 1.1rem; opacity: 0.9; font-weight: 400;">
            Manage your account, post items for sale, and review your purchases.
        </p>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <strong>Profile Saved!</strong> Your information has been updated successfully.
        <button type="button" class="btn-close" data-bs-alert="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<section class="display-div dashboard-scroll" id="dashboardAccordion">
    
    <article class="card mb-3">
        <div class="d-flex align-items-center border-bottom bg-body-tertiary">
            <header class="card-header border-0 flex-grow-1 d-flex justify-content-between align-items-center mb-0" 
                    style="cursor: pointer; background: transparent;" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#profileBody">
                <span>Profile Summary</span>
                <span class="collapse-icon">▼</span>
            </header>
            <div class="pe-3 ms-3 border-start ps-3 d-flex align-items-center" style="height: 24px; min-width: 85px;">
                <a href="edit_profile.php" class="btn btn-sm btn-outline-secondary edit-btn-collapse">Edit</a>
            </div>
        </div>
        <div id="profileBody" class="collapse show" data-bs-parent="#dashboardAccordion">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">Name:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></dd>
                    
                    <dt class="col-sm-4 text-muted fw-normal">Username:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($user['username']); ?></dd>
                    
                    <dt class="col-sm-4 text-muted fw-normal">Email:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($user['email']); ?></dd>
                    
                    <dt class="col-sm-4 text-muted fw-normal">Account Role:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($user['role_name']); ?> (Level <?php echo $user['level']; ?>)</dd>
                    
                    <dt class="col-sm-4 text-muted fw-normal">Member Since:</dt>
                    <dd class="col-sm-8"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></dd>
                </dl>
            </div>
        </div>
    </article>

    <article class="card mb-3">
        <div class="d-flex align-items-center border-bottom bg-body-tertiary">
            <header class="card-header border-0 flex-grow-1 d-flex justify-content-between align-items-center mb-0 collapsed" 
                    style="cursor: pointer; background: transparent;" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#postItemsBody">
                <span>Your Listings</span>
                <span class="collapse-icon">▼</span>
            </header>
            <div class="pe-3 ms-3 border-start ps-3 d-flex align-items-center" style="height: 24px; min-width: 85px;"></div>
        </div>
        <div id="postItemsBody" class="collapse" data-bs-parent="#dashboardAccordion">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="btn-group btn-group-sm product-filters" role="group">
                        <input type="radio" class="btn-check" name="statusFilter" id="btnLive" value="1" checked>
                        <label class="btn btn-outline-secondary" for="btnLive">Live</label>
                        <input type="radio" class="btn-check" name="statusFilter" id="btnSold" value="3">
                        <label class="btn btn-outline-secondary" for="btnSold">Sold</label>
                        <input type="radio" class="btn-check" name="statusFilter" id="btnAll" value="all">
                        <label class="btn btn-outline-secondary" for="btnAll">Both</label>
                    </div>                  
                    <a href="add_product.php" class="btn btn-success btn-sm">Add New Product</a>
                </div>

                <div class="list-group list-group-flush" id="userProductHistory">
                    <?php if (empty($myProducts)): ?>
                        <div class="list-group-item bg-transparent text-muted small">No items listed.</div>
                    <?php else: ?>
                        <?php foreach ($myProducts as $item): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-reset border-secondary product-item" 
                                 data-status="<?php echo $item['status_id']; ?>">
                                <div>
                                    <span class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <span class="badge rounded-pill <?php echo $item['status_id'] == 1 ? 'bg-success' : 'bg-secondary'; ?> ms-2 opacity-75 fw-normal" style="font-size: 0.7rem;">
                                        <?php echo htmlspecialchars($item['status_label']); ?>
                                    </span>
                                    <?php if ($item['status_id'] == 3 && $item['buyer_name']): ?>
                                        <br><small class="text-success">Purchased by: <?php echo htmlspecialchars($item['buyer_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <a href="edit_product.php?id=<?php echo $item['product_id']; ?>" class="btn btn-outline-info btn-sm py-0">Edit</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </article>

    <article class="card mb-3">
        <div class="d-flex align-items-center border-bottom bg-body-tertiary">
            <header class="card-header border-0 flex-grow-1 d-flex justify-content-between align-items-center mb-0 collapsed" 
                    style="cursor: pointer; background: transparent;" 
                    data-bs-toggle="collapse"
                    data-bs-target="#historyBody">
                <span>Purchase History</span>
                <span class="collapse-icon">▼</span>
            </header>
            <div class="pe-3 ms-3 border-start ps-3 d-flex align-items-center" style="height: 24px; min-width: 85px;"></div>
        </div>
        <div id="historyBody" class="collapse" data-bs-parent="#dashboardAccordion">
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php if (empty($myPurchases)): ?>
                        <div class="list-group-item bg-transparent border-secondary text-reset">No purchases found.</div>
                    <?php else: ?>
                        <?php foreach ($myPurchases as $order): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-reset border-secondary">
                                <div>
                                    <strong>Order #<?php echo $order['order_id']; ?>: <?php echo htmlspecialchars($order['item_name']); ?></strong>
                                    <br><small class="opacity-75"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold d-block">£<?php echo number_format($order['total_amount'], 2); ?></span>
                                    <button class="btn btn-sm btn-outline-warning" style="font-size: 0.7rem;">Feedback</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </article>
</section>

<script>
    <?php 
    include 'assets/js/main.js'; 
    ?>
</script>
<?php include 'includes/footer.php'; ?>
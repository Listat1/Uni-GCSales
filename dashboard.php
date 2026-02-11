<?php
// dashboard.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?msg=auth");
    exit;
}

// Set up Notifications
$msg = strtolower(trim($_GET['msg'] ?? ''));
$alertType = null; // Clear, just in case
switch ($msg) {
    case 'updated':
        $alertType = 'success';
        $strongText = "{$_GET['item']} Saved! ";
        $message = "Your " . strtolower($_GET['item']) . " has been updated successfully.";
        break;

    case 'locked':
        $alertType = 'warning';
        $strongText = 'Action Locked!';
        $message = 'Save failed. Modified product has been sold and cannot be edited.';
        break;

    case 'error':
        $alertType = 'danger';
        $strongText = 'Error!';
        $message = 'Something went wrong while saving your changes.';
        break;

    default:
        // unknown or missing msg, no alert
        unset($alertType, $strongText, $message, $_GET['msg'], $msg);
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

// Display Page
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

<?php    

// Display Notifications
if ($alertType) {
    echo <<<HTML
    <div class="alert alert-{$alertType} alert-dismissible fade show mb-4 shadow-sm" role="alert">
        <strong>{$strongText}</strong> {$message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    HTML;
}
// Reset the Notification before another page picks them up accidently.
unset($alertType, $strongText, $message, $_GET['msg'], $msg);
?>

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
                    <?php
                    // Get item status
                    $stmt = $pdo->query("SELECT status_id, status_label FROM product_status ORDER BY status_id");
                    $status = $stmt->fetchAll();
                    ?>
                        <div class="btn-group btn-group-sm product-filters" role="group">
                        <?php 
                        // Add Display Filter buttons - Options from database
                        foreach ($status as $state): 
                            $id = 'btn' . $state['status_label']; 
                        ?>
                            <input class="btn-check" 
                                name="statusFilter" 
                                id="<?php echo $id; ?>" 
                                type="radio" 
                                value="<?php echo $state['status_id']; ?>">
                            <label class="btn btn-outline-secondary" for="<?php echo $id; ?>"><?php echo htmlspecialchars($state['status_label']); ?></label>
                        <?php 
                        endforeach;
                        // Add Default 'Show All' Filter 
                        ?>
                        <input class="btn-check" name="statusFilter" id="btnAll" type="radio" value="all" checked>
                        <label class="btn btn-outline-secondary" for="btnAll">All</label>
                        </div>            
                    <a href="add_product.php" class="btn btn-success btn-sm">Add New Product</a>
                </div>

                <div class="list-group list-group-flush" id="userProductHistory">
                    <?php
                    // Build list of items listed by user 
                    if (empty($myProducts)):
                    ?>
                        <div class="list-group-item bg-transparent text-muted small">No items listed.</div>
                    <?php 
                    else:
                        // Assign status styles based on status_id in an array
                        // Future: Create table using database lookup, allowing for more catagories to be added dynamically.
                        $statusStyles = [
                            1 => ['badge' => 'bg-success', 'btnLabel' => 'Edit', 'btnClass' => 'btn-outline-info'],
                            2 => ['badge' => 'bg-warning text-dark', 'btnLabel' => 'Finalize', 'btnClass' => 'btn-warning'],
                            3 => ['badge' => 'bg-secondary', 'btnLabel' => 'View', 'btnClass' => 'btn-outline-secondary'],
                        ];
                        foreach ($myProducts as $item): 
                            // Assign colour and button styles based on status
                            $style = $statusStyles[$item['status_id']];
                            $badgeClass = $style['badge'];
                            $btnLabel   = $style['btnLabel'];
                            $btnClass   = $style['btnClass'];
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-reset border-secondary product-item" 
                                data-status="<?php echo $item['status_id']; ?>">
                                <div>
                                    <span class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></span>
                                    
                                    <span class="badge rounded-pill <?php echo $badgeClass; ?> ms-2 opacity-75 fw-normal" style="font-size: 0.7rem;">
                                        <?php echo htmlspecialchars($item['status_label']); ?>
                                    </span>

                                    <?php if ($item['status_id'] == 3 && !empty($item['buyer_name'])): ?>
                                        <br><small class="text-success">Purchased by: <?php echo htmlspecialchars($item['buyer_name']); ?></small>
                                    <?php elseif ($item['status_id'] == 2): ?>
                                        <br><small class="text-warning italic">Awaiting final sign-off</small>
                                    <?php endif; ?>
                                </div>

                                <a href="edit_product.php?id=<?php echo $item['product_id']; ?>" 
                                class="btn <?php echo $btnClass; ?> btn-sm py-0">
                                <?php echo $btnLabel; ?>
                                </a>
                            </div>
                        <?php 
                        endforeach;
                    endif; 
                    ?>
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
                    <?php
                    // Build list of items purchsed by user
                    if (empty($myPurchases)): 
                        ?>
                        <div class="list-group-item bg-transparent border-secondary text-reset">No purchases found.</div>
                    <?php
                    else:
                        foreach ($myPurchases as $order):
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-reset border-secondary">
                                <div>
                                    <strong>
                                        <?php echo 'Order #' . $order['order_id'] . ': ' . htmlspecialchars($order['item_name']); ?>
                                    </strong>
                                    <br>
                                    <small class="opacity-75">
                                        <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold d-block">
                                        £<?php echo number_format($order['total_amount'], 2); ?>
                                    </span>
                                    <button class="btn btn-sm btn-outline-warning" style="font-size: 0.7rem;">
                                        Feedback
                                    </button>
                                </div>
                            </div>
                        <?php
                        endforeach; 
                    endif; 
                    ?>
                </div>
            </div>
        </div>
    </article>


    <?php
    // Admins Only
    if($user['level'] >= 30):
        ?>
        <article class="card mb-3">
            <div class="d-flex align-items-center border-bottom bg-body-tertiary">
                <header class="card-header border-0 flex-grow-1 d-flex justify-content-between align-items-center mb-0 collapsed"
                        style="cursor: pointer; background: transparent;"
                        data-bs-toggle="collapse"
                        data-bs-target="#adminBody">
                    <span>Administration</span>
                    <span class="collapse-icon">▼</span>
                </header>
                <div class="pe-3 ms-3 border-start ps-3 d-flex align-items-center" style="height: 24px; min-width: 85px;"></div>
            </div>
            
            <div id="adminBody" class="collapse" data-bs-parent="#dashboardAccordion">
                <div class="card-body">
                    <form method="POST" action="api/proc_admin.php" id="adminForm">
                        <div class="mb-3">
                            <label for="userSelect" class="form-label">Select User:</label>
                            <select name="user_id" id="userSelect" class="form-select" required>
                                <option value="">
                                    -- Select a user --
                                </option>
                                <?php
                                $stmt = $pdo->query(
                                        "SELECT * 
                                        FROM users 
                                        WHERE user_id != {$user['user_id']} 
                                        ORDER BY username");
                                while($u = $stmt->fetch()){
                                    echo '<option value="'. $u['user_id'] .'">'. htmlspecialchars($u['username'] .' ('. $u['email'] .')') .'</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="newLevel" class="form-label">
                                Set Access Level:
                            </label>
                            <select name="level" id="newLevel" class="form-select" required>
                                <?php
                                $stmt = $pdo->query(
                                    "SELECT * 
                                    FROM roles 
                                    ORDER BY auth_level");
                                while($r = $stmt->fetch()){
                                    if($r['auth_level'] <= $user['level']){
                                        echo '<option value="'. $r['auth_level'] .'">'. htmlspecialchars($r['role_name'] .' (Level '. $r['auth_level'] .')') .'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3 d-flex gap-2">
                            <button type="submit" name="action" value="promote" class="btn btn-primary btn-sm">
                                Change Level
                            </button>
                            <button type="submit" name="action" value="reset" class="btn btn-warning btn-sm">
                                Reset Password
                            </button>
                            <?php 
                            if ($user['level'] >= 40): 
                                ?>
                                <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" 
                                    onclick="return confirm('Full Purge: This anonymises the user and PERMANENTLY deletes all associated addresses. Proceed?')">
                                    Delete User
                                </button>
                            <?php 
                            endif; 
                            ?>
                        </div>
                    </form>
                    <hr>
                    <small class="text-muted d-block">
                        <strong>Moderation:</strong> Allows for password resets and managing user access levels.<br>
                        <?php if ($user['level'] >= 40): ?>
                            <strong>Full Delete:</strong> Anonymises the profile (scrubs personal data) and purges all address records from the database.
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </article>
    <?php
    endif;
    ?>
</section>

<script>
    <?php 
    include 'assets/js/main.js'; 
    ?>
</script>
<?php include 'includes/footer.php'; ?>
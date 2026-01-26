<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



session_start();
require_once 'includes/dbconnection.php';
$pdo = getDatabaseConnection();
// No Guests
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=login_required");
    exit;
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G&C Sells - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <script>
        // Single Source of Truth: Theme Logic
        const applyTheme = (theme) => {
            document.documentElement.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
        };

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        }

        const savedTheme = localStorage.getItem('theme') || 'dark';
        applyTheme(savedTheme);
    </script>
    <style>
        /* Your existing styles remain exactly the same */
        :root {
            --teal-base: #3b8ea5; --teal-dark: #2a7082; --teal-light: #5aaec4;
            --card-bg: #ffffff; --body-bg: #f4f7f9; --item-bg: #ffffff;
            --item-text: #000000; --profile-text: #000000;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --card-bg: #2b2b2b; --body-bg: #1f1f1f; --item-bg: #383838;
                --item-text: #e0e0e0; --profile-text: #ffffff;
            }
        }
        html, body { height: 100%; margin: 0; }
        body { display: flex; flex-direction: column; background: var(--body-bg); color: #222; }
        @media (prefers-color-scheme: dark) { body { color: #e0e0e0; } }
        main { flex: 1; display: flex; flex-direction: column; padding: 0.5rem; overflow: hidden; }
        .navbar { background-color: var(--teal-dark) !important; box-shadow: 0 2px 6px rgba(0,0,0,0.2); }
        .hero { background: linear-gradient(to bottom right, #1f4f5d, var(--teal-dark)); padding: 2rem; text-align: center; border-radius: 10px; margin-bottom: 1rem; color: white; box-shadow: 0 3px 12px rgba(0,0,0,0.3); }
        .hero h2 { font-size: 1.75rem; margin-bottom: 0.5rem; font-weight: 600; }
        .dashboard-content { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 1.2rem; padding-right: 4px; }
        .card { border: none; border-radius: 12px; background: var(--card-bg); box-shadow: 0 4px 12px rgba(0,0,0,0.12); transition: all 0.3s ease; }
        .card-header { background-color: var(--teal-base); color: white; font-weight: 600; padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .card-header .collapse-btn { background: rgba(255,255,255,0.2); border: none; color: white; border-radius: 4px; width: 28px; height: 28px; }
        .card-body { padding: 1rem 1.2rem; }
        .list-group-item { background-color: var(--item-bg); color: var(--item-text); border: none; border-bottom: 1px solid #4a4a4a; }
        .btn-custom { padding: 0.45rem 1.1rem; border-radius: 8px; font-weight: 500; border: none; text-decoration: none; display: inline-block; }
        .btn-edit { background: var(--teal-base); color: white; }
        .btn-add { background: #2ecc71; color: white; }
        .btn-feedback { background: #e67e22; color: white; }
        footer { background-color: var(--teal-dark); color: white; text-align: center; padding: 12px; margin-top: auto; }
        .desktop-header, .desktop-col { display: block; }
        .mobile-col { display: none; }
        @media (max-width: 768px) {
            .desktop-header, .desktop-col { display: none !important; }
            .mobile-col { display: grid; grid-template-columns: max-content auto; gap: 0.25rem 0.75rem; padding: 0.25rem 0; border-bottom: 1px solid #4a4a4a; position: relative; }
            .mobile-col div { display: contents; }
            .mobile-col .btn-edit { position: absolute; top: 0.25rem; right: 0; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark border-bottom fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">Grimsby & Clee Sells</a>
        
        <button class="btn btn-sm btn-outline-light me-2" onclick="toggleTheme()">
            🌓 Switch Mode
        </button>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto" id="navList">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="basket.php">Basket</a></li>
                <li class="nav-item"><a class="nav-link" href="api/proc_logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container">
    <div class="hero">
        <?php 
            $fullName = htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        ?>
        <h2>Welcome, <?php echo trim($fullName) ?: 'User'; ?>!</h2>
        <p>Manage your account, post items for sale, and review your purchases.</p>
    </div>

    <div class="dashboard-content">

        <div class="card">
            <div class="card-header" data-bs-toggle="collapse" data-bs-target="#profileBody">
                Profile Summary
                <button class="collapse-btn">−</button>
            </div>
            <div id="profileBody" class="collapse show">
            <div class="card-body">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                <p><strong>Account Created:</strong> <?php echo isset($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
                <p><strong>Membership Level:</strong> <?php echo htmlspecialchars($user['level'] ?? '10'); ?></p>
                <a href="edit_profile.php" class="btn-custom btn-edit">Edit Profile</a>
            </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" data-bs-toggle="collapse" data-bs-target="#postItemsBody">
                Post Item for Sale
                <button class="collapse-btn">−</button>
            </div>
            <div id="postItemsBody" class="collapse show">
                <div class="card-body">
                    <a href="add_product.php" class="btn-custom btn-add mb-3">Add New Product</a>

                    <div class="list-group">
                        <div class="list-group-item d-flex fw-bold desktop-header" style="border-bottom: 2px solid var(--teal-base);">
                            <div style="flex: 1;">Ref:</div>
                            <div style="flex: 2;">Posted</div>
                            <div style="flex: 2;">Price</div>
                            <div style="flex: 4;">Description</div>
                            <div style="flex: 1;"></div>
                        </div>

                        <?php if (empty($myProducts)): ?>
                            <div class="list-group-item">No items listed yet. Start selling today!</div>
                        <?php else: foreach ($myProducts as $item): ?>
                            <div class="list-group-item d-flex align-items-center flex-wrap">
                                <div class="desktop-col" style="flex:1;"><?php echo str_pad($item['product_id'], 3, '0', STR_PAD_LEFT); ?></div>
                                <div class="desktop-col" style="flex:2;"><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></div>
                                <div class="desktop-col" style="flex:2;">£<?php echo number_format($item['price'], 2); ?></div>
                                <div class="desktop-col" style="flex:4;"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="desktop-col" style="flex:1;"><a href="edit_product.php?id=<?php echo $item['product_id']; ?>" class="btn-custom btn-edit btn-sm">Edit</a></div>

                                <div class="mobile-col w-100">
                                    <div><strong>Ref:</strong><span><?php echo str_pad($item['product_id'], 3, '0', STR_PAD_LEFT); ?></span></div>
                                    <div><strong>Posted:</strong><span><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></span></div>
                                    <div><strong>Price:</strong><span>£<?php echo number_format($item['price'], 2); ?></span></div>
                                    <div><strong>Description:</strong><span><?php echo htmlspecialchars($item['name']); ?></span></div>
                                    <a href="edit_product.php?id=<?php echo $item['product_id']; ?>" class="btn-custom btn-edit btn-sm">Edit</a>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" data-bs-toggle="collapse" data-bs-target="#historyBody">
                Purchase History
                <button class="collapse-btn">−</button>
            </div>
            <div id="historyBody" class="collapse show">
                <div class="card-body">
                    <div class="list-group">
                        <?php if (empty($myPurchases)): ?>
                            <div class="list-group-item">No purchases found.</div>
                        <?php else: foreach ($myPurchases as $order): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($order['order_id']); ?></strong><br>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></small>
                                </div>
                                <div>
                                    <span class="me-3 fw-bold">£<?php echo number_format($order['total_price'], 2); ?></span>
                                    <button class="btn-custom btn-feedback btn-sm">Leave Feedback</button>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<footer>Terms & Conditions | Privacy Policy | Contact</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Logic for toggling the button text (+ / -) when using Bootstrap Collapse
    document.querySelectorAll('.collapse').forEach(el => {
        el.addEventListener('hide.bs.collapse', () => {
            el.parentElement.querySelector('.collapse-btn').textContent = '+';
        });
        el.addEventListener('show.bs.collapse', () => {
            el.parentElement.querySelector('.collapse-btn').textContent = '−';
        });
    });
</script>
</body>
</html>
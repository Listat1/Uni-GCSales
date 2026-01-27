<?php
// index.php
    // Initialise Database
    require_once 'includes/dbconnection.php';
    $db = getDatabaseConnection();
    // Get List of filtered products (If any)
    require_once 'includes/db_select.php';
    require_once 'includes/components.php'; 
    $q = $_GET['q'] ?? '';
    $cat = $_GET['cat'] ?? '';
    $products = (empty($q) && empty($cat)) ? [] : searchProducts($db, $q, $cat);
    // Get Header
    $pageTitle = "Grimsby and Clee Sells";
    $pageID = "home";
    include 'includes/header.php';
?>

<section class="hero" id="heroBanner">
    <img src="assets/graphics/ui/hero_desktop.webp" alt="Dock Tower" class="hero-bg-img">
    <div id="heroContent">
            Buy Local<br>Sell Local<br>Be Local
    </div>
    <div id="selectedCat"></div>
    <div id="heroCategories" class="category-grid">
        <?php
        // Load Categories in reverse order making "Everything Else" (id 1 ) last on the list
        $stmt = $db->query(
            "SELECT category_id, category_name
            FROM categories
            ORDER BY (category_id = 1) ASC, category_id DESC
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<button class="btn cat-grid-btn" data-id="' . $row['category_id'] . '">';
            echo htmlspecialchars($row['category_name']);
            echo '</button>';
        }
        ?>
    </div>    
</section>

<section class="login-register sticky-top" id="loginDiv">
    <div class="container-fluid d-flex justify-content-center py-2">
        <button class="btn btn-auth-primary me-2" id="loginBtn">Login</button>
        <button class="btn btn-auth-secondary" id="registerBtn">Register</button>
    </div>
</section>

<section class="search-bar sticky-top" id="searchBar">
    <form id="searchForm" action="index.php" method="GET" class="w-100 d-flex">
        <input type="text" name="q" id="searchInput" class="form-control" 
            value="<?= htmlspecialchars($q) ?>" placeholder="Search for products..." 
            autocomplete="off">
        <input type="hidden" name="cat" id="hiddenCatID" value="">
        <button type="button" class="btn ms-2" id="browseBtn">Select Category</button>
    </form>
</section>

<section class="display-div" id="productArea">
    <?php if (empty($products)): ?>
        Search Results...
    <?php else: ?>
    <?php foreach ($products as $p): ?>
        <div class="result-card mb-3">
            <?= renderProductCard($p) ?>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php 
    include 'includes/footer.php';
    // Initial Display kept hidden until required (login selected)
    include 'includes/mod_auth.php';
?>
<script>
    <?php include 'assets/js/main.js'; ?>
    <?php include 'assets/js/auth.js'; ?>    
</script>
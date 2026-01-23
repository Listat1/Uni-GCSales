<?php
// index.php
    // Initialise Database
    require_once 'includes/dbconnection.php';
    $db = getDatabaseConnection();
    // Get List of filtered products (If any)
    require_once 'includes/db_select.php';
    $q = $_GET['q'] ?? '';
    $cat = $_GET['cat'] ?? '';
    $products = (empty($q) && empty($cat)) ? [] : searchProducts($db, $q, $cat);
    // Get Header
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
    <button class="btn me-2" id="loginBtn">Login</button>
    <button class="btn btn-outline-light">Register</button>
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
            <div class="card-body">
                <div class="d-flex gap-3">
                    <img src="<?= htmlspecialchars($p['display_img']) ?>" class="rounded thumb-img" style="width:110px; height:110px; object-fit:cover;">
                    <div class="flex-grow-1">
                        <!-- Components displayed on card: -->
                        <h6 class="mb-1 fw-bold"><?= htmlspecialchars($p['name']) ?></h6>
                        <h5 class="mb-1">£<?= htmlspecialchars($p['price']) ?></h5>
                        <p class="mb-2 small text-secondary"><?= htmlspecialchars($p['description']) ?></p>
                        <button 
                        class="btn btn-sm btn-outline-primary" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#details-<?= $p['product_id'] ?>">
                            View Details
                        </button>
                    </div>
                </div>
                <div class="collapse" id="details-<?= $p['product_id'] ?>">
                    <hr>
                    <div class="carousel-placeholder bg-dark text-center py-4 mb-3 rounded">
                        [Carousel Swiper Here]
                    </div>
                    <p class="full-desc"><?= htmlspecialchars($p['long_description'] ?? 'No further details available.') ?></p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success flex-grow-1">Buy Now</button>
                        <button class="btn btn-primary flex-grow-1">Add to Basket</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</section>

<script>
    // After Document has loaded:
    document.addEventListener('DOMContentLoaded', () => {
        const browseBtn = document.getElementById('browseBtn');
        const heroBanner = document.getElementById('heroBanner');
        const heroCategories = document.getElementById('heroCategories');
        const loginDiv = document.getElementById('loginDiv');
        const loginBtn = document.getElementById('loginBtn');
        const productArea = document.getElementById('productArea');
        const searchInput = document.getElementById('searchInput');
        const hiddenCatID = document.getElementById('hiddenCatID');
        const selectedCatDisplay = document.getElementById('selectedCat');
        // Get the heights of <nav> and #loginDiv
        const navHeight = document.querySelector('nav').offsetHeight; 
        // Assign (Start) offsets to the sticky-top divs
        if (loginDiv) {
            loginDiv.style.top = `${navHeight}px`;
            const loginDivHeight = loginDiv.offsetHeight;
            searchBar.style.top = `${navHeight + loginDivHeight}px`;
        } else {
            searchBar.style.top = `${navHeight}px`;
        }

        // Monitor "Select Category" button
        if (browseBtn) {
            browseBtn.addEventListener('click', (e) => {
                // console.log("Browse Button Clicked!"); // Debug: Displays in Browsers Console
                e.preventDefault(); // Stop form (container) from submitting
                heroBanner.classList.toggle('show-categories');
                browseBtn.classList.toggle('btn-active');
            });
        }
        // Display Category Selection
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('cat-grid-btn')) {
                const btn = e.target;
                const catID = btn.getAttribute('data-id'); // Hidden Element in form
                const catName = btn.innerText;             // HR name
                // Update Status at bottom of the hero
                selectedCatDisplay.innerText = "Current Category: " + catName;
                // Store the ID in the hidden form field ready for submit
                hiddenCatID.value = catID; 
                // Switch Hero to default display
                heroBanner.classList.remove('show-categories');
                browseBtn.classList.remove('btn-active');
                // console.log("Form is now primed with Category ID:", catID); // Debug:
                // Trigger LiveSearch (If required)
                triggerLiveSearch(); // Prerequisites tested by function
            }
        });

        // Fetch data if 3+ characters typed in search bar
        async function triggerLiveSearch() {
            const q = searchInput.value.trim();
            const cat = hiddenCatID.value;
            // Only start pulling list if 3 or more characters
            if (q.length < 3) {
                productArea.innerHTML = q.length > 0 ? "Keep typing..." : "Search Results...";
                return;
            }
            try {
                // Fetch results from the API
                const response = await fetch(`api/fetch_product_list.php?q=${encodeURIComponent(q)}&cat=${cat}`);
                const products = await response.json();
                productArea.innerHTML = "";
                if (products.length > 0) {
                    // Build list of products from search results
                    products.forEach(p => {
                        const card = document.createElement("div");
                        card.className = "result-card mb-3";
                        // Using p.display_img, p.description, and p.price from SQL keys
                        // (var) card.innerHTML = productArea card structure, small for product list,
                        // expanded when user selects an item. CSS controls sizing:
                        const collapseId = `details-${p.product_id}`; 
                        card.innerHTML = `
                            <div class="card-body">
                                <div class="d-flex gap-3">
                                    <img src="${p.display_img}" class="rounded thumb-img" style="width:110px; height:110px; object-fit:cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold">${p.name}</h6>
                                        
                                        <div class="mb-1">
                                            <span class="badge bg-success">£${p.price}</span>
                                        </div>
                                        <p class="mb-2 small text-secondary">${p.description}</p>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#${collapseId}">
                                            View Details
                                        </button>
                                    </div>
                                </div>

                                <div class="collapse" id="${collapseId}">
                                    <hr>
                                    <div class="carousel-placeholder bg-dark text-center py-4 mb-3 rounded">
                                        [Carousel Swiper Here]
                                    </div>
                                    <p class="full-desc">${p.long_description || 'No further details available.'}</p>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-success flex-grow-1">Buy Now</button>
                                        <button class="btn btn-primary flex-grow-1">Add to Basket</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        productArea.appendChild(card);
                    });

                    // Manually initialise Bootstrap Collapse for the newly injected HTML
                    const collapseElements = productArea.querySelectorAll('.collapse');
                    collapseElements.forEach(el => {
                        new bootstrap.Collapse(el, { toggle: false });
                    });

                } else {
                    productArea.innerHTML = "<em>No results found in database. Press Enter to refresh.</em>";
                }
            } catch (err) {
                console.error("LiveSearch Error:", err);
                productArea.innerHTML = "<em>Error connecting to search service.</em>";
            }
        }
        // "Search Form Input" listener for the "3+ characters"
        searchInput.addEventListener('input', triggerLiveSearch);

        loginBtn.addEventListener('click', () => {
            // Completely remove the Nag to free layout space
            if (loginDiv) loginDiv.remove();
            // Add the new Nav items
            const navList = document.getElementById('navList');
            ['Dashboard', 'Basket', 'Logout'].forEach(item => {
                const li = document.createElement('li');
                li.classList.add('nav-item');
                li.innerHTML = `<a class="nav-link" href="#">${item}</a>`;
                navList.appendChild(li);
            });
            // Update Search Bar position to snap flush against <Nav>
            // Uses !important to ensure CSS cannot overide whilst session is active
            searchBar.style.setProperty('top', `${navHeight}px`, 'important');
        });
        // Listen for when a Bootstrap collapse starts showing
        // Lazy Loading implementation for Bootstrap cards
        // Only load Full Description and images when requested
        document.addEventListener('show.bs.collapse', async (e) => {
            const productCard = e.target; 
            const productId = productCard.id.replace('details-', '');
            // Selecting the areas within the card to be populated
            const descriptionArea = productCard.querySelector('.full-desc');
            const carouselArea = productCard.querySelector('.carousel-placeholder');
            const currentText = descriptionArea.innerText;
            const needsLoading = currentText.includes('available') || currentText.includes('Loading') || currentText.includes('Failed');
            // Fetch Full Description if not loaded 
            if (needsLoading) {
                descriptionArea.innerHTML = '<em>Refreshing product card...</em>';
                try {
                    // Get details from database
                    const response = await fetch(`api/fetch_product_details.php?id=${productId}`);
                    const data = await response.json();
                    // Update expanded view with full content
                    descriptionArea.innerHTML = `
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <strong class="text-primary">${data.name}</strong>
                            <span class="text-success fw-bold">£${data.price}</span>
                        </div>
                        <div class="product-long-desc">${data.long_description || 'No further specs.'}</div>
                    `;
                    // Inject new images into Carousel from database if they exist.
                    if (data.images && data.images.length > 0) {
                        let carouselHtml = `
                            <div id="carousel-${productId}" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">`;
                        data.images.forEach((path, index) => {
                            carouselHtml += `
                                <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                    <img src="${path}" class="d-block w-100 rounded-top" style="height:250px; object-fit:contain; background:#000;">
                                </div>`;
                        });
                        carouselHtml += `
                            </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel-${productId}" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carousel-${productId}" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                </button>
                            </div>
                            <div class="bg-dark text-white-50 small py-1 rounded-bottom border-top border-secondary text-center">
                                Image <span id="count-${productId}">1</span> of ${data.images.length}
                            </div>`;
                        carouselArea.innerHTML = carouselHtml;
                        // Listener to update the numbers on slide
                        const carouselEl = document.getElementById(`carousel-${productId}`);
                        const countSpan = document.getElementById(`count-${productId}`);
                        carouselEl.addEventListener('slide.bs.carousel', (event) => {
                            // event.to is the index of the next item (0-based)
                            countSpan.innerText = event.to + 1;
                        });
                    }
                    else {
                        carouselArea.innerHTML = '<p class="text-muted small text-center">No additional images available.</p>';
                    }
                } catch (err) {
                    console.error("Card Update Error:", err);
                    descriptionArea.innerHTML = '<span class="text-danger">Connection lost. Click to try again.</span>';
                }
            }
        });
    // End of DOMContentLoaded
    }); 
</script>
<?php 
    // Get Footer
    include 'includes/footer.php';
?>
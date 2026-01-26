// Global Clickable items Listener
document.addEventListener('click', async (e) => {
    
    if (e.target.id === 'loginBtn' || e.target.id === 'registerBtn') {
            setAuthView(e.target.id === 'loginBtn' ? 'login' : 'register');
            authModal.show();
            return;
    }
    // Actions to take after logout
    const logoutTrigger = e.target.closest('#logoutBtn');
    if (logoutTrigger) {
        e.preventDefault();
        try {
            const response = await fetch('api/proc_logout.php');
            const result = await response.json();
            if (result.success) {
                // Perform a Hard Reset of the UI state via reload
                window.location.reload();
            }
        } catch (err) {
            console.error("Logout Sequence Error:", err);
        }
        return;
    }
    // Check for Category Selection (Delegated)
    const catBtn = e.target.closest('.cat-grid-btn');
    if (catBtn) { 
        const catID = catBtn.getAttribute('data-id');
        const catName = catBtn.innerText;  
        const display = document.getElementById('selectedCat');
        if (display) display.innerText = "Current Category: " + catName;
        
        document.getElementById('hiddenCatID').value = catID;
        heroBanner.classList.remove('show-categories');
        if (browseBtn) browseBtn.classList.remove('btn-active');
        
        triggerLiveSearch();
    }       
});
// Actions to take after login is confirmed
function LoginNavUpdate() {
    const loginContainer = document.getElementById('loginDiv');
    if (loginContainer) loginContainer.remove();    
    
    const navList = document.getElementById('navList');
    if (navList) {
        ['Dashboard', 'Basket', 'Logout'].forEach(item => {
            const li = document.createElement('li');
            li.classList.add('nav-item');
            // Add Logout with #id
            const idAttr = item === 'Logout' ? 'id="logoutBtn"' : '';
            li.innerHTML = `<a class="nav-link" ${idAttr} href="#">${item}</a>`;
            navList.appendChild(li);
        });
    }
}
// Monitor "Select Category" button
const browseBtn = document.getElementById('browseBtn');
const heroBanner = document.getElementById('heroBanner');
if (browseBtn) {
    browseBtn.addEventListener('click', (e) => {
        e.preventDefault(); 
        heroBanner.classList.toggle('show-categories');
        browseBtn.classList.toggle('btn-active');
        if (heroBanner.classList.contains('show-categories')) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
}
// LiveSearch: Fetch data if 3+ characters typed in search bar
const searchInput = document.getElementById('searchInput');
const productArea = document.getElementById('productArea');
// Setting hiddenCatID not required, but does speed up query lookup.
const hiddenCatID = document.getElementById('hiddenCatID');
// Monitor 'Enter' in Search Bar
searchInput.addEventListener('input', triggerLiveSearch);

async function triggerLiveSearch() {
    const q = searchInput.value.trim();
    const cat = hiddenCatID.value;
    if (q.length < 3) {
        productArea.innerHTML = q.length > 0 ? "Keep typing..." : "Search Results...";
        return;
    }
    try {
        const response = await fetch(`api/fetch_product_list.php?q=${q}&cat=${document.getElementById('hiddenCatID').value}`);
        const products = await response.json();
        productArea.innerHTML = "";
        if (products.length > 0) {
            products.forEach(p => {
                const cardContainer = document.createElement("div");
                cardContainer.className = "result-card mb-3";
                cardContainer.innerHTML = p.card_html;
                productArea.appendChild(cardContainer);
                // Notify BS .collapse of new card
                new bootstrap.Collapse(cardContainer.querySelector('.collapse'), { toggle: false });
            });
        } else {
            productArea.innerHTML = "<em>No results found. Press Enter to refresh.</em>";
        }
    } catch (err) { 
        console.error("LiveSearch Error:", err);
        productArea.innerHTML = "<em>Error connecting to search service.</em>";
    }
}
// Action taken when event with BSclass:collapse triggered.
// Scoped specifically to productArea to avoid Navbar collisions.
// Lazy Loading implementation: Full Details and images loaded only when requested.
if (productArea) {
    productArea.addEventListener('show.bs.collapse', async (e) => {
        // The specific collapse element being opened
        const detailContainer = e.target; 
        const productId = detailContainer.id.replace('details-', '');
        const detailBody = detailContainer.querySelector('.full-desc');
        const carouselArea = detailContainer.querySelector('.carousel-placeholder');

        // Skip if no detail body exists in DOM
        if (!detailBody) return;

        // Test status with HTML5 Data Attribute
        const status = detailBody.getAttribute('data-status');

        if (status === 'pending') {
            // Immediately transition to 'loading' to prevent double-triggers (Debounce)
            detailBody.setAttribute('data-status', 'loading');
            detailBody.innerHTML = '<em>Refreshing product card...</em>';

            try {
                const response = await fetch(`api/fetch_product_details.php?id=${productId}`);
                const data = await response.json();
                
                // Inject sanitised data from API
                detailBody.innerHTML = `
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                        <strong class="text-primary">${data.name}</strong>
                        <span class="text-success fw-bold">£${data.price}</span>
                    </div>
                    <div class="product-long-desc">${data.long_description}</div>
                `;

                // Carousel Implementation
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
                    
                    const carouselEl = document.getElementById(`carousel-${productId}`);
                    const countSpan = document.getElementById(`count-${productId}`);
                    carouselEl.addEventListener('slide.bs.carousel', (event) => {
                        countSpan.innerText = event.to + 1;
                    });
                } else {
                    carouselArea.innerHTML = '<p class="text-muted small text-center">No additional images available.</p>';
                }

                // Set Status - HTML5 Data Attribute - to completed
                detailBody.setAttribute('data-status', 'complete');

            } catch (err) {
                console.error("Card Update Error:", err);
                // Reset state on error so the user can attempt a retry
                detailBody.setAttribute('data-status', 'pending');
                detailBody.innerHTML = '<span class="text-danger">Connection lost. Click to try again.</span>';
            }
        }
    });
}
// #### SHARED FUNCTIONS
const currentPage = document.body.getAttribute('data-page-id');
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
                window.location.href = 'index.php?msg=lo';
            }
        } catch (err) {
            console.error("Logout Sequence Error:", err);
        }
        return;
    }      
});
// -- GEO TOOLS (Unified Lookup & Reset) --
let isProcessingGeo = false;

async function performLookup() {
    const pcField = document.getElementById('reg_postcode');
    const houseNumField = document.getElementById('reg_house_num');
    const verifyStatus = document.getElementById('verifyStatus');
    const detailArea = document.getElementById('addressDetails');

    if (!pcField || !verifyStatus || isProcessingGeo) return;

    const pc = pcField.value.trim().toUpperCase();
    const houseNum = houseNumField ? houseNumField.value.trim() : '';

    if (!pc) return;
    isProcessingGeo = true;

    try {
        const response = await fetch(`https://api.postcodes.io/postcodes/${pc}`);
        const data = await response.json();

        verifyStatus.classList.remove('bg-body-secondary');

        if (data.status === 200) {
            const res = data.result;

            // 1. Determine Town Name
            let townName = res.bua || res.ttwa || res.parish || res.admin_district;
            townName = townName.replace(/, unparished area|District|Borough/gi, '').trim();

            // 2. UI Feedback
            verifyStatus.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-lg"></i> Verified</span>';
            verifyStatus.className = "input-group-text bg-success-subtle border-success text-success";

            // 3. Update City Immediately
            const cityField = document.getElementById('reg_city');
            if (cityField) cityField.value = townName;

            // 4. Update Street with Placeholder
            if (detailArea) {
                detailArea.style.opacity = "1";
                detailArea.style.pointerEvents = "auto";

                const streetField = document.getElementById('reg_address_1');
                if (streetField) {
                    if (pc === "DN31 1AA") {
                        streetField.value = "Town Hall Square";
                    } else {
                        streetField.value = `[Street for ${pc}]`; 
                    }
                    streetField.classList.add('is-valid'); 
                }
            }
        } 
        else {
            // Failure UI (Invalid Postcode)
            verifyStatus.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-x-lg"></i> Invalid</span>';
            verifyStatus.className = "input-group-text bg-danger-subtle border-danger text-danger";
            if (detailArea) {
                detailArea.style.opacity = "0.5";
                detailArea.style.pointerEvents = "none";
            }
        }
    } 
    catch (err) {
        console.error("Geo Lookup Error:", err);
    } 
    finally {
        isProcessingGeo = false;
    }
} // <--- End of performLookup

// Global Event Listeners for Geo Tools
document.addEventListener('input', (e) => {
    if (e.target.id === 'reg_postcode') {
        const vs = document.getElementById('verifyStatus');
        const da = document.getElementById('addressDetails');
        if (vs) {
            vs.innerHTML = '<span class="text-muted opacity-50">Pending...</span>';
            vs.className = "input-group-text bg-body-secondary border-primary-subtle";
        }
        if (da) {
            da.style.opacity = "0.5";
            da.style.pointerEvents = "none";
        }
    }
});

document.addEventListener('keydown', (e) => {
    if (e.target.id === 'reg_postcode' || e.target.id === 'reg_house_num') {
        if (e.key === 'Enter') {
            e.preventDefault(); 
            performLookup();
        }
        if (e.key === 'Tab' && !e.shiftKey) {
            performLookup();
        }
    }
});

document.addEventListener('focusout', (e) => {
    if (e.target.id === 'reg_postcode' || e.target.id === 'reg_house_num') {
        performLookup();
    }
});

// Highlight the active page in the Navbar
const activeLink = document.querySelector(`#navList a[href="${currentPage === 'home' ? 'index.php' : currentPage + '.php'}"]`);
if (activeLink) activeLink.classList.add('active', 'fw-bold');


document.addEventListener('DOMContentLoaded', function() {
    const filterContainer = document.querySelector('.product-filters');
    if (!filterContainer) return; 

    const filterButtons = document.querySelectorAll('input[name="statusFilter"]');
    const productItems = document.querySelectorAll('.product-item');

    filterButtons.forEach(button => {
        button.addEventListener('change', function() {
            const selectedStatus = this.value;

            productItems.forEach(item => {
                const itemStatus = item.getAttribute('data-status');
                if (selectedStatus === 'all' || itemStatus === selectedStatus) {
                    item.classList.replace('d-none', 'd-flex');
                } else {
                    item.classList.replace('d-flex', 'd-none');
                }
            });
        });
    });
});

// INDEX.PHP ONLY CODE //
if (currentPage === 'home') {
    // 1. Grab all elements
    const browseBtn = document.getElementById('browseBtn');
    const heroBanner = document.getElementById('heroBanner');
    const searchInput = document.getElementById('searchInput');
    const productArea = document.getElementById('productArea');
    const hiddenCatID = document.getElementById('hiddenCatID');

    // 2. Define Function (Hoisted)
    async function triggerLiveSearch() {
        if (!searchInput || !productArea || !hiddenCatID) return;
        const q = searchInput.value.trim();
        const cat = hiddenCatID.value;
        
        if (q.length < 3) {
            productArea.innerHTML = q.length > 0 ? "Keep typing..." : "Search Results...";
            return;
        }
        try {
            const response = await fetch(`api/fetch_product_list.php?q=${q}&cat=${cat}`);
            const products = await response.json();
            
            productArea.innerHTML = "";
            
            products.forEach(p => {
                const cardContainer = document.createElement("div");
                cardContainer.className = "result-card mb-3";
                cardContainer.innerHTML = p.card_html;
                productArea.appendChild(cardContainer);
                new bootstrap.Collapse(cardContainer.querySelector('.collapse'), { toggle: false });
            });

            // Add invisible spacer to allow bottom cards to scroll to top
            // Initialized with no transition so expansion is instant when triggered
            const spacer = document.createElement("div");
            spacer.id = "scroll-spacer";
            spacer.style.height = "0px";
            productArea.appendChild(spacer);
        } 
        catch (err) { console.error("LiveSearch Error:", err); }
    }

    // 3. Category Toggle (Guarded)
    if (browseBtn && heroBanner) {
        browseBtn.addEventListener('click', (e) => {
            e.preventDefault(); 
            heroBanner.classList.toggle('show-categories');
            browseBtn.classList.toggle('btn-active');
        });
    }

    // 4. Category Selection (Delegated)
    document.addEventListener('click', (e) => {
        const catBtn = e.target.closest('.cat-grid-btn');
        if (catBtn) {
            const display = document.getElementById('selectedCat');
            if (display) display.innerText = "Current Category: " + catBtn.innerText;
            if (hiddenCatID) hiddenCatID.value = catBtn.getAttribute('data-id');
            
            if (heroBanner) heroBanner.classList.remove('show-categories');
            if (browseBtn) browseBtn.classList.remove('btn-active');
            
            triggerLiveSearch();
        }
    });

    // 5. Search Input (Guarded)
    if (searchInput) {
        searchInput.addEventListener('input', triggerLiveSearch);
    }

    // Action taken when event with BSclass:collapse triggered.
    // Scoped specifically to productArea to avoid Navbar collisions.
    // Lazy Loading implementation: Full Details and images loaded only when requested.
if (productArea) {
        productArea.addEventListener('show.bs.collapse', (e) => {
            const card = e.target.closest('.result-card');
            const spacer = document.getElementById('scroll-spacer');
            
            if (card) {
                // 1. THE BUFFER
                // Expand spacer instantly (no transition) to provide immediate scroll runway
                if (spacer) {
                    spacer.style.transition = "none";
                    spacer.style.height = "50vh";
                    spacer.style.display = "block"; 
                }

                setTimeout(() => {
                    card.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    })
                }, 100);

                // 2. THE MATH (Absolute Page Coordinates)
                // Timeout allows the DOM to acknowledge the spacer and the expanding card
                setTimeout(() => {
                    const cardPageTop = window.pageYOffset + card.getBoundingClientRect().top;
                    
                    const navH = document.querySelector('.navbar')?.offsetHeight || 0;
                    const loginH = (document.getElementById('loginDiv')?.offsetHeight > 0) 
                                   ? document.getElementById('loginDiv').offsetHeight : 0;
                    const searchH = document.getElementById('searchBar')?.offsetHeight || 0;
                    
                    const totalStickyHeight = navH + loginH + searchH;

                    // 3. THE JUMP
                    window.scrollTo({
                        top: cardPageTop - totalStickyHeight,
                        behavior: 'smooth'
                    });
                }, 100);
            }       
            
            // 4. DATA FETCHING
            const detailContainer = e.target; 
            const productId = detailContainer.id.replace('details-', '');
            const detailBody = detailContainer.querySelector('.full-desc');
            const carouselArea = detailContainer.querySelector('.carousel-placeholder');

            if (detailBody && detailBody.getAttribute('data-status') === 'pending') {
                detailBody.setAttribute('data-status', 'loading');
                detailBody.innerHTML = '<em>Refreshing product card...</em>';

                (async () => {
                    try {
                        const response = await fetch(`api/fetch_product_details.php?id=${productId}`);
                        const data = await response.json();
                        
                        detailBody.innerHTML = `
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <strong class="text-primary">${data.name}</strong>
                                <span class="text-success fw-bold">£${data.price}</span>
                            </div>
                            <div class="product-long-desc">${data.long_description}</div>
                        `;

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

                        detailBody.setAttribute('data-status', 'complete');

                    } catch (err) {
                        console.error("Card Update Error:", err);
                        detailBody.setAttribute('data-status', 'pending');
                        detailBody.innerHTML = '<span class="text-danger">Error loading details.</span>';
                    }
                })();
            }
        });

        // Remove virtual space when card is closed
        // Transition added here so the footer slides back up smoothly
        productArea.addEventListener('hide.bs.collapse', () => {
            const spacer = document.getElementById('scroll-spacer');
            if (spacer) {
                spacer.style.transition = "height 0.4s ease";
                spacer.style.height = "0px";
            }
        });
    }
} // End of if(currentPage === 'home')
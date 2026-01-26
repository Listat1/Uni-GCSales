<?php
// includes/components.php
// designed to reduce duplication of code 
function renderProductCard($p) {
    // Returns Markup to calling code.
    // PHP can echo to screen, JS can process as a JSON.
    $collapseId = "details-" . $p['product_id'];
    return '
    <div class="card-body">
        <div class="d-flex gap-3">
            <img 
                src="'.htmlspecialchars($p['display_img']).'" 
                class="rounded thumb-img" 
                style="width:110px; height:110px; object-fit:cover;">
            </img>
            <div class="flex-grow-1">
                <h6 class="mb-1 fw-bold">
                    '.htmlspecialchars($p['name']).'
                </h6>
                <div class="mb-1">
                    <span class="badge bg-success">
                        £'.htmlspecialchars($p['price']).'
                    </span>
                </div>
                <p class="mb-2 small text-secondary">
                    '.htmlspecialchars($p['description']).'
                </p>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#'.$collapseId.'">
                    View Details
                </button>
            </div>
        </div>
        <div class="collapse" id="'.$collapseId.'">
            <hr>
            <div class="carousel-placeholder bg-dark text-center py-4 mb-3 rounded">
                [Carousel Swiper Here]
            </div>
            <p class="full-desc" data-status="pending">
                <span class="text-muted">Loading details...</span>
            </p>
            <div class="d-flex gap-2">
                <button class="btn btn-success flex-grow-1">Buy Now</button>
                <button class="btn btn-primary flex-grow-1">Add to Basket</button>
            </div>
        </div>
    </div>';
}
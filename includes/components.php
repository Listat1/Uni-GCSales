<?php
// includes/components.php
// Created to reduce duplication of code.
// Compare URL to test if Direct access used.
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Direct access not allowed');
}
function renderProductCard($p) {
// Render detailed product card used in the product search list. - Image, price badge,
// short_description, and collapsible section for full_descriptionn and buy now/basket.
// Returns Markup using heredoc to client. PHP can echo markup, whilst JS can process it as a JSON.
    $collapseId = "details-" . $p['product_id'];
    
    // Sanitise variables used in return
    $img     = htmlspecialchars($p['display_img']);
    $name    = htmlspecialchars($p['name']);
    $price   = htmlspecialchars($p['price']);
    $desc    = htmlspecialchars($p['description']);
    $prodId  = (int)$p['product_id'];

    return <<<HTML
    <div class="card-body border-bottom">
        <div class="d-flex gap-3">
            <img 
                src="{$img}" 
                class="rounded thumb-img" 
                style="width:110px; height:110px; object-fit:cover;"
                alt="{$name}">
            <div class="flex-grow-1">
                <h6 class="mb-1 fw-bold">
                    {$name}
                </h6>
                <div class="mb-1">
                    <span class="badge bg-success">
                        £{$price}
                    </span>
                </div>
                <p class="mb-2 small text-secondary">
                    {$desc}
                </p>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#{$collapseId}">
                    View Details
                </button>
            </div>
        </div>
        
        <div class="collapse" id="{$collapseId}">
            <hr>
            <div class="carousel-placeholder bg-dark text-center py-4 mb-3 rounded text-white-50">
                <small>[Carousel Swiper Here]</small>
            </div>
            
            <p class="full-desc" data-status="pending" data-product-id="{$prodId}">
                <span class="text-muted">Loading details...</span>
            </p>
            
            <div class="d-flex gap-2">
                <!-- Instant Buy Form -->
                <form class="flex-grow-1" action="checkout.php" method="POST">
                    <input type="hidden" name="product_id" value="{$prodId}">
                    <input type="hidden" name="instant_buy" value="1">
                    <button class="btn btn-success w-100" type="submit">
                        Buy Now
                    </button>
                </form>

                <!-- Add to Basket Form -->
                <form class="flex-grow-1" action="api/proc_basket.php" method="POST">
                    <input type="hidden" name="product_id" value="{$prodId}">
                    <button class="btn btn-primary w-100" type="submit">
                        Add to Basket
                    </button>
                </form>
            </div>
        </div>
    </div>
HTML;
}
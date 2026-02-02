<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content auth-modal-content">
            
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">GCSales : Login</h5>
                <button type="button" class="btn-close" id="modalCloseBtn" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div id="authFeedback" class="alert d-none small py-2"></div>

                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label auth-label">Username</label>
                        <input type="text" name="username" class="form-control auth-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label auth-label">Password</label>
                        <input type="password" name="password" class="form-control auth-input" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Login</button>
                    <p class="text-center mt-3 small">
                        Not a member? <a href="#" id="linkToRegister">Register here</a>
                    </p>
                </form>

                <form id="registerForm" class="d-none">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small">First Name</label>
                            <input type="text" name="reg_first_name" class="form-control" required placeholder="John">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Last Name</label>
                            <input type="text" name="reg_last_name" class="form-control" required placeholder="Doe">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Pick a Username</label>
                        <input type="text" name="reg_username" class="form-control" required placeholder="jdoe88">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Email</label>
                        <input type="email" name="reg_email" class="form-control" required placeholder="name@example.com">
                    </div>

<div class="mb-3 p-3 border rounded shadow-sm" style="background-color: rgba(0,0,0,0.02);">
    <label class="form-label fw-bold text-primary mb-0">Delivery Details (Local Only)</label>
    <div class="text-muted mb-3" style="font-size: 0.75rem;">
        Within 10 miles of Grimsby. <span class="text-secondary">(Press Enter/Tab for testing)</span>
    </div>

<div class="mb-3">
    <div class="d-flex justify-content-between align-items-end mb-1">
        <label class="form-label small mb-0">Postcode</label>
        <span class="text-muted" style="font-size: 0.65rem;">powered by postcodes.io</span>
    </div>
    <div class="input-group">
        <input type="text" name="reg_postcode" id="reg_postcode" 
               class="form-control fw-bold border-primary" 
               value="DN31 1AA" required>
        <span class="input-group-text bg-body-secondary border-primary-subtle" 
              id="verifyStatus" 
              style="min-width: 110px; font-size: 0.75rem; transition: all 0.3s ease;">
            <span class="text-muted opacity-50">Pending...</span>
        </span>
    </div>
    <div class="form-text" style="font-size: 0.65rem;">Tab or Enter to verify your area.</div>
</div>

    <div class="mb-3">
        <label class="form-label small">House/Flat Number</label>
        <input type="text" name="reg_house_num" id="reg_house_num" 
               class="form-control" 
               placeholder="e.g. 10 or Flat 1" required>
        <div id="numWarning" class="text-danger d-none" style="font-size: 0.7rem;">Please enter a number first.</div>
    </div>

    <div id="addressDetails" class="mt-2" style="opacity: 0.5; pointer-events: none;">
        <div class="mb-3">
            <label class="form-label small">Street Address</label>
            <input type="text" name="reg_address_1" id="reg_address_1" 
                   class="form-control" placeholder="Awaiting postcode lookup...">
        </div>
        
        <div class="row g-2 align-items-end">
            <div class="col-8">
                <label class="form-label small">City</label>
                <input type="text" name="reg_city" id="reg_city" 
                       class="form-control" value="Grimsby" readonly>
            </div>
        </div>
    </div>
                    <div class="mb-3">
                        <label class="form-label small">Create Password</label>
                        <input type="password" name="reg_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Create Account</button>

                    <p class="text-center mt-3 small">
                        Already a member? <a href="#" id="linkToLogin">Login here</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
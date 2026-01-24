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
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label auth-label">First Name</label>
                        <input type="text" name="reg_first_name" class="form-control auth-input" placeholder="Graham" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label auth-label">Last Name</label>
                        <input type="text" name="reg_last_name" class="form-control auth-input" placeholder="Refurb" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label auth-label">Pick a Username</label>
                    <input type="text" name="reg_username" class="form-control auth-input" placeholder="e.g. GrimsbyRefurb" required>
                </div>

                <div class="mb-3">
                    <label class="form-label auth-label">Email</label>
                    <input type="email" name="reg_email" class="form-control auth-input" placeholder="e.g. smiths@example.com" required>
                </div>

                <div class="mb-3">
                    <label class="form-label auth-label">Create Password</label>
                    <input type="password" name="reg_password" class="form-control auth-input" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold">Create Account</button>
                
                <p class="text-center mt-3 small">
                    Already a member? <a href="#" id="linkToLogin">Login here</a>
                </p>
            </form>
        </div>
    </div>
</div>
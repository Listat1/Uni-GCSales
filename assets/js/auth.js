// assets/js/auth.js

// 1. Grab the Modal elements
const authModalEl = document.getElementById('authModal');
const authModal = authModalEl ? new bootstrap.Modal(authModalEl) : null;
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const toggleAuthBtn = document.getElementById('toggleAuth');
const modalTitle = document.getElementById('modalTitle');

// 2. View Switcher Logic
function setAuthView(view) {
    if (view === 'register') {
        loginForm.classList.add('d-none');
        registerForm.classList.remove('d-none');
        modalTitle.innerText = "Join the Community";
    } else {
        registerForm.classList.add('d-none');
        loginForm.classList.remove('d-none');
        modalTitle.innerText = "G&C Sales: Login";
    }
}

// 3. Attach Listeners to the buttons in index.php
if (loginBtn) {
    loginBtn.addEventListener('click', () => {
        setAuthView('login');
        authModal.show();
    });
}

if (registerBtn) {
    registerBtn.addEventListener('click', () => {
        setAuthView('register');
        authModal.show();
    });
}

// 4. Handle Form Submissions (The Brains)
[loginForm, registerForm].forEach(form => {
    if(!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        formData.append('action', form.id === 'loginForm' ? 'login' : 'register');

        const response = await fetch('api/proc_auth.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            // Trigger your "Nag Removal" logic
            LoginNavUpdate(); // Update the NAV bar
            authModal.hide(); // Remove the login screen 
        } else {
            alert(result.message); // Replace with your authFeedback div later
        }
    });
});
document.addEventListener('click', (e) => {
    if (e.target.id === 'linkToRegister') {
        e.preventDefault();
        setAuthView('register');
    }
    if (e.target.id === 'linkToLogin') {
        e.preventDefault();
        setAuthView('login');
    }
});

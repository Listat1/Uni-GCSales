// assets/js/auth.js
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
// Assign Login / Register switch links in Modal
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
// Login and Register switcher
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
// Manage Login / Register Process
const modalObject = document.getElementById('authModal');
const modalTitle = document.getElementById('modalTitle'); // Move this up
const authModal = modalObject ? new bootstrap.Modal(modalObject) : null;
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
            window.location.href = 'dashboard.php';            
        } else {
            alert(result.message);
        }
    });
});

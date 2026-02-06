// assets/js/auth.js
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const modalObject = document.getElementById('authModal');
const modalTitle = document.getElementById('modalTitle');
const authModal = modalObject ? new bootstrap.Modal(modalObject) : null;

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
    // Clear errors when switching
    document.querySelectorAll('.form-error-box').forEach(el => el.style.display = 'none');
    
    if (view === 'register') {
        loginForm.classList.add('d-none');
        registerForm.classList.remove('d-none');
        if(modalTitle) modalTitle.innerText = "Join the Community";
    } else {
        registerForm.classList.add('d-none');
        loginForm.classList.remove('d-none');
        if(modalTitle) modalTitle.innerText = "G&C Sales: Login";
    }
}
// Manage Login / Register Display
[loginForm, registerForm].forEach(form => {
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const errorBox = form.querySelector('.form-error-box');

        const formData = new FormData(form);
        formData.append('action', form.id === 'loginForm' ? 'login' : 'register');

        try {
            const response = await fetch('api/proc_auth.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                window.location.href = 'dashboard.php';
            } else {
                if (errorBox) {
                    errorBox.className = "form-error-box alert alert-warning shadow-sm";
                    errorBox.innerHTML = `<strong>Notice:</strong> ${result.message}`;
                    errorBox.style.display = 'block';

                    if (form.id === 'registerForm') {
                        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    alert(result.message);
                }
            }
        } catch (err) {
            console.error("Auth error:", err);
            if (errorBox) {
                errorBox.className = "form-error-box alert alert-danger shadow-sm";
                errorBox.textContent = "Server connection lost. Please try again.";
                errorBox.style.display = 'block';
            }
        }
    });

    form.querySelectorAll('input:not([type="submit"]):not([type="button"])').forEach(input => {
        input.addEventListener('input', () => {
            const errorBox = form.querySelector('.form-error-box');
            if (errorBox) {
                errorBox.style.display = 'none';
            }
        });
    });
}); 

if (modalObject) {
    modalObject.addEventListener('hidden.bs.modal', () => {
        [loginForm, registerForm].forEach(form => {
            if (form) {
                form.reset(); 
                const errorBox = form.querySelector('.form-error-box');
                if (errorBox) {
                    errorBox.style.display = 'none';
                    errorBox.innerHTML = '';
                }
            }
        });
        setAuthView('login'); 
    });
}
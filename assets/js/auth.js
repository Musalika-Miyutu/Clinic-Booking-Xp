// Get DOM Elements
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const toRegisterLink = document.getElementById('toRegister');
const toLoginLink = document.getElementById('toLogin');

// Switch to Register Form
toRegisterLink.addEventListener('click', function(e) {
    e.preventDefault(); // Prevent default link click behavior
    loginForm.classList.add('hidden');
    registerForm.classList.remove('hidden');
});

// Switch to Login Form
toLoginLink.addEventListener('click', function(e) {
    e.preventDefault();
    registerForm.classList.add('hidden');
    loginForm.classList.remove('hidden');
});
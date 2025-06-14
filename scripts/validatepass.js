        
function togglePassword() {
    const input = document.getElementById('password');
    const button = document.querySelector('.password-toggle');
    
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '🙈';
    } else {
        input.type = 'password';
        button.textContent = '👁️';
    }
}
//DEBUGGGGGGG
document.addEventListener('DOMContentLoaded', function() {
    console.log('Login page loaded successfully!');
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.focus();
    }
});
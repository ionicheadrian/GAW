let currentForm = 'login'; //incem cu ideea ca loginul o sa fie mereu primul 

function updateSigupHeight() {
    // functia asta am creeat o pt ca formul de login si cel de signul
    // au heighturi diferite, iar pentru o tranzitie mia seamless
    // trb sa le setam dupa cum este apasat fiecare buton
    const container = document.querySelector('.form-container');
    const activeForm = document.querySelector('.form.active') || document.querySelector('.form:not(.hidden)');

    if (activeForm) {
        const height = activeForm.scrollHeight;
        container.style.height = height + 'px';
    }
}

function showLogin() {
    if (currentForm === 'login') return;

    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const container = document.querySelector('.form-container');

    //calculam inaltimea login formulului
    loginForm.style.position = 'static';
    loginForm.classList.remove('hidden'); //ii scoatem clasa hidden atlfe n am putea sa calc inaltimea
    const loginHeight = loginForm.scrollHeight;
    loginForm.style.position = 'absolute';
    loginForm.classList.add('hidden');
    // setam inaltimea containerului parinte pt k loginul
    // este mult mai mic decat signupul
    container.style.height = loginHeight + 'px';


    //animatia de tranzitie sa fie cat mai seamless si nice :D
    signupForm.classList.remove('active');
    signupForm.classList.add('slide-out-right');


    //aici vine partea interesanta
    // functia asta este adaugata pentru a adauga un delay de 200ms
    // ca tranzitia slide-out-right signup formului sa se termine (complet sau partial)
    setTimeout(() => {
        signupForm.classList.add('hidden');
        signupForm.classList.remove('slide-out-right', 'active'); // aici signup formul devine hidden

        loginForm.classList.remove('hidden');
        loginForm.classList.add('active', 'fade-in');
    }, 200);

    // aici setam corect starea butoanelor 
    document.querySelectorAll('.toggle')[0].classList.add('active');
    document.querySelectorAll('.toggle')[1].classList.remove('active');

    //in final setam variabila in starea corecta
    currentForm = 'login';
}

function showSignup() {
    if (currentForm === 'signup') return;

    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const container = document.querySelector('.form-container');

    signupForm.style.position = 'static';
    signupForm.classList.remove('hidden');
    const signupHeight = signupForm.scrollHeight;
    signupForm.style.position = 'absolute';
    signupForm.classList.add('hidden');

    container.style.height = signupHeight + 'px';

    loginForm.classList.remove('active');
    loginForm.classList.add('slide-out-left');

    setTimeout(() => {
        loginForm.classList.add('hidden');
        loginForm.classList.remove('slide-out-left', 'active');

        signupForm.classList.remove('hidden');
        signupForm.classList.add('active', 'fade-in');

    }, 200);

    document.querySelectorAll('.toggle')[1].classList.add('active');
    document.querySelectorAll('.toggle')[0].classList.remove('active');

    currentForm = 'signup';
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;

    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '🙈';
    } else {
        input.type = 'password';
        button.textContent = '👁️';
    }
}

//validarea emailului in timp real
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

//validarea emailului in timp real
function validatePassword(password) {
    const requirements = {
        length: password.length >= 8,//verifica daca are cel putin 8 caractere
        upper: /[A-Z]/.test(password),//daca are macar o litera mare
        lower: /[a-z]/.test(password),// daca are litere mici
        number: /\d/.test(password), // macar un numar
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password) // macar un caracter special
    };

    return requirements;
}

document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');

    loginForm.classList.add('active');
    signupForm.classList.add('hidden');

    setTimeout(() => {
        updateSigupHeight();
    }, 100);

    const emailInput = document.getElementById('signupEmail');
    const emailValidation = document.getElementById('emailValidation');

    emailInput.addEventListener('input', function () {
        const email = this.value;

        if (email.length === 0) {
            emailValidation.style.display = 'none';
            setTimeout(updateSigupHeight, 100);
            return;
        }

        if (validateEmail(email)) {
            emailValidation.className = 'email-validation valid';
            emailValidation.textContent = '✓ Email valid';
        } else {
            emailValidation.className = 'email-validation invalid';
            emailValidation.textContent = '✗ Formatul email-ului nu este corect';
        }

        setTimeout(updateSigupHeight, 100);
    });

    const passwordInput = document.getElementById('signupPassword');
    const passwordRequirements = document.getElementById('passwordRequirements');

    passwordInput.addEventListener('input', function () {
        const password = this.value;
        const requirements = validatePassword(password);

        Object.keys(requirements).forEach(req => {
            const element = document.getElementById(`req-${req}`);
            if (requirements[req]) {
                element.classList.add('valid');
            } else {
                element.classList.remove('valid');
            }
        });
        const allValid = Object.values(requirements).every(req => req);
        if (allValid) {
            passwordRequirements.classList.add('valid');
        } else {
            passwordRequirements.classList.remove('valid');
        }

        setTimeout(updateSigupHeight, 100);
    });

    const confirmPasswordInput = document.getElementById('signupConfirmPassword');

    confirmPasswordInput.addEventListener('input', function () {
        const password = passwordInput.value;
        const confirmPassword = this.value;

        if (confirmPassword.length === 0) {
            this.style.borderColor = '#e0e0e0';
            return;
        }

        if (password === confirmPassword) {
            this.style.borderColor = '#4CAF50';
        } else {
            this.style.borderColor = '#f44336';
        }
    });
    window.addEventListener('resize', () => {
        setTimeout(updateSigupHeight, 100);
    });
});

document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    if (!validateEmail(email)) {
        alert('Te rog să introduci un email valid!');
        return;
    }

    if (password.length < 6) {
        alert('Parola trebuie să aibă cel puțin 6 caractere!');
        return;
    }

    alert('Form de login trimis! (demo)\nEmail: ' + email);
});

document.getElementById('signupForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const email = document.getElementById('signupEmail').value;
    const password = document.getElementById('signupPassword').value;
    const confirmPassword = document.getElementById('signupConfirmPassword').value;
    const fullName = document.getElementById('signupName').value;

    if (!validateEmail(email)) {
        alert('Te rog să introduci un email valid!');
        return;
    }

    const passwordReqs = validatePassword(password);
    const allPasswordReqsMet = Object.values(passwordReqs).every(req => req);

    if (!allPasswordReqsMet) {
        alert('Parola nu îndeplinește toate cerințele!');
        return;
    }

    if (password !== confirmPassword) {
        alert('Parolele nu coincid!');
        return;
    }

    if (fullName.length < 2) {
        alert('Te rog să introduci numele complet!');
        return;
    }

    alert('Cont creat cu succes! (demo)\nNume: ' + fullName + '\nEmail: ' + email + '\nRol: Cetățean (default)');
});
//functia de toggle la parola
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '\ud83d\ude48';
    } else {
        input.type = 'password';
        button.textContent = '\ud83d\udc41\ufe0f';
    }
}

//verif parola in timp real
function validatePassword(password) {
    //verificam fiecare rerinta a parolei
    let hasMinLength = false;
    if(password.length >= 8) 
        hasMinLength = true;
    console.log("lungimea parolei este ",hasMinLength);
    const hasUppercase = /[A-Z]/.test(password); //returneaza true daca exita o litera mare
    const hasLowercase = /[a-z]/.test(password); //mica
    const hasNumber = /[0-9]/.test(password); //are numar
    const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);//caracter special

    //acum facem actualizarile in timp real pe pagina 

    // selectam elementul 
    const lengthElement = document.getElementById('req-length');
    if (lengthElement) {
        //selectam iconita
        const lengthIcon = lengthElement.querySelector('.requirement-icon');
        
        //updatam stateul dupa test
        if (hasMinLength) {
            lengthElement.classList.remove('invalid');
            lengthElement.classList.add('valid');
            lengthIcon.textContent = '\u2713';
        } else {
            lengthElement.classList.remove('valid'); 
            lengthElement.classList.add('invalid');
            lengthIcon.textContent = '\u2717';
        }
    }
    
    //literile mari
    const uppercaseElement = document.getElementById('req-uppercase');
    if (uppercaseElement) {
        const uppercaseIcon = uppercaseElement.querySelector('.requirement-icon');
        if (hasUppercase) {
            uppercaseElement.classList.remove('invalid');
            uppercaseElement.classList.add('valid');
            uppercaseIcon.textContent = '\u2713';
        } else {
            uppercaseElement.classList.remove('valid');
            uppercaseElement.classList.add('invalid');
            uppercaseIcon.textContent = '\u2717';
        }
    }
    
    //literele mica
    const lowercaseElement = document.getElementById('req-lowercase');
    if (lowercaseElement) {
        const lowercaseIcon = lowercaseElement.querySelector('.requirement-icon');
        if (hasLowercase) {
            lowercaseElement.classList.remove('invalid');
            lowercaseElement.classList.add('valid');
            lowercaseIcon.textContent = '\u2713';
        } else {
            lowercaseElement.classList.remove('valid');
            lowercaseElement.classList.add('invalid');
            lowercaseIcon.textContent = '\u2717';
        }
    }
    //nr
    const numberElement = document.getElementById('req-number');
    if (numberElement) {
        const numberIcon = numberElement.querySelector('.requirement-icon');
        if (hasNumber) {
            numberElement.classList.remove('invalid');
            numberElement.classList.add('valid');
            numberIcon.textContent = '\u2713';
        } else {
            numberElement.classList.remove('valid');
            numberElement.classList.add('invalid');
            numberIcon.textContent = '\u2717';
        }
    }
    //caracter special
    const specialElement = document.getElementById('req-special');
    if (specialElement) {
        const specialIcon = specialElement.querySelector('.requirement-icon');
        if (hasSpecial) {
            specialElement.classList.remove('invalid');
            specialElement.classList.add('valid');
            specialIcon.textContent = '\u2713';
        } else {
            specialElement.classList.remove('valid');
            specialElement.classList.add('invalid');
            specialIcon.textContent = '\u2717';
        }
    }
    
    //dupa multe calcule logice (cred ca era mai usor sa le adum si daca le adunam si comparam rez cu 5)
    //returneaza true doar daca toate sunt true
    return hasMinLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
}

//verificam daca parolele coincid (parola vs cparola)
function checkPasswordMatch() {
    const passwordInput = document.getElementById('password') || document.getElementById('new_password'); //prima parola (new pass este folosita pentru forgotpass)
    const confirmPasswordInput = document.getElementById('confirm_password'); //cparola
    const matchIndicator = document.getElementById('password-match');
    const matchText = document.getElementById('match-text');


    
    //verificare void inputs
    if (!passwordInput || !confirmPasswordInput || !matchIndicator) {
        return; 
    }
    //luam valorile
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;
    //daca confirm pass este null ascundem semnu
    if (confirmPassword.length === 0) {
        matchIndicator.classList.remove('show');
        return;
    }
    // daca pana acm nu am iesit din functie inseamna ca avem toate tipurile de inputuri
    // trebuie doar sa le testam 
    matchIndicator.classList.add('show');
    
    if (password === confirmPassword) { //all good
        matchIndicator.className = 'match-indicator show match-yes'; //schimbam css-ul
        matchText.textContent = '\u2713 Parolele coincid'; //schimbam textul acelui element
    } else {
        matchIndicator.className = 'match-indicator show match-no'; //schimbam css-ul
        matchText.textContent = '\u2717 Parolele nu coincid'; //schimbam textul acelui element
    }
}


//event listenerul  :O (todo divizat codul in controalare, models, views alea alea)
document.addEventListener('DOMContentLoaded', function() {
    console.log('Password validation script loaded successfully!');
    const firstInput = document.getElementById('full_name') || 
                      document.getElementById('email') || 
                      document.querySelector('input[type="text"], input[type="email"]');
    if (firstInput) {
        firstInput.focus();
    }
    
    //REGISTER CODE (todo separam prin controalare sau ceva)
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            validatePassword(this.value); //verificam daca passwordinput este valida
            checkPasswordMatch();       //verificam daca parola coincide cparola
        });
    }
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }   //aici verificam daca cparola coincide cu parola
        //verificam de 2 doua ori pentru ca odata listenerul poate asa asculte
        //inputul parolei, alta data inputul cparolei asa ca mereu trebuie sa le verificam pt REALTIME
        //updates 
    




    // FORGOT CODE (todo separam prin controalare sau ceva)
    const newPasswordInput = document.getElementById('new_password');
    const confirmNewPasswordInput = document.getElementById('confirm_password');
    
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            validatePassword(this.value);
            checkPasswordMatch();
        });
    }
    
    if (confirmNewPasswordInput) {
        confirmNewPasswordInput.addEventListener('input', checkPasswordMatch);
    }
    




    
    //cod pentru LOGIN (todo separam prin controalare sau ceva)
    const emailInput = document.getElementById('email');
    if (emailInput && !firstInput) {
        emailInput.focus();
    }
});
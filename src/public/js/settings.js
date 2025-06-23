let hasUnsavedChanges = false;
let originalFormData = {};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Edit Profile JavaScript loaded successfully!');
    initFormValidation();
    initUnsavedChangesDetection();
    initConfirmations();
    initTooltips();
    initAnimations();
});

function initFormValidation() {
    const form = document.querySelector('.profile-form');
    if (!form) return;
    const formData = new FormData(form);
    for (let [key, value] of formData.entries()) {
        originalFormData[key] = value;
    }
    
    const fullNameField = document.getElementById('full_name');
    if (fullNameField) {
        fullNameField.addEventListener('input', function() {
            validateFullName(this);
        });
        
        fullNameField.addEventListener('blur', function() {
            validateFullName(this);
        });
    }

    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.addEventListener('input', function() {
            validateUsername(this);
        });
        
        usernameField.addEventListener('blur', function() {
            validateUsername(this);
        });
    }

    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.addEventListener('input', function() {
            validateEmail(this);
        });
        
        emailField.addEventListener('blur', function() {
            validateEmail(this);
        });
    }

    const phoneField = document.getElementById('phone');
    if (phoneField) {
        phoneField.addEventListener('input', function() {
            validatePhone(this);
        });
        
        phoneField.addEventListener('blur', function() {
            validatePhone(this);
        });
    }
    
    console.log('Form validation initialized');
}

function validateFullName(field) {
    const value = field.value.trim();
    
    if (value.length === 0) {
        setFieldState(field, 'neutral');
        return true;
    }
    
    if (value.length < 2) {
        setFieldState(field, 'error', 'Numele trebuie sa aiba cel putin 2 caractere');
        return false;
    }
    
    if (!/^[a-zA-ZăâîșțĂÂÎȘȚ\s\-'\.]+$/.test(value)) {
        setFieldState(field, 'error', 'Numele poate contine doar litere, spatii si caracterele \'-\'.');
        return false;
    }
    
    setFieldState(field, 'success', 'Nume valid');
    return true;
}

function validateUsername(field) {
    const value = field.value.trim();
    
    if (value.length === 0) {
        setFieldState(field, 'neutral');
        return true;
    }
    
    if (value.length < 3) {
        setFieldState(field, 'error', 'Username-ul trebuie sa aiba cel putin 3 caractere');
        return false;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(value)) {
        setFieldState(field, 'error', 'Username-ul poate contine doar litere, cifre si underscore');
        return false;
    }
    
    if (value.length > 20) {
        setFieldState(field, 'error', 'Username-ul nu poate avea mai mult de 20 de caractere');
        return false;
    }
    
    setFieldState(field, 'success', 'Username valid');
    return true;
}

function validateEmail(field) {
    const value = field.value.trim();
    
    if (value.length === 0) {
        setFieldState(field, 'neutral');
        return true;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(value)) {
        setFieldState(field, 'error', 'Formatul email-ului nu este valid');
        return false;
    }
    
    setFieldState(field, 'success', 'Email valid');
    return true;
}

function validatePhone(field) {
    const value = field.value.trim();
    
    if (value.length === 0) {
        setFieldState(field, 'neutral');
        return true;
    }
    
    const phoneRegex = /^(\+4|0040|0)[0-9]{9}$|^(\+|00)[1-9][0-9]{7,14}$/;

    const cleanPhone = value.replace(/[\s\-()]/g, '');
    
    if (!phoneRegex.test(cleanPhone)) {
        setFieldState(field, 'error', 'Formatul telefonului nu este valid (ex: 0712345678)');
        return false;
    }
    
    setFieldState(field, 'success', 'Telefon valid');
    return true;
}

function setFieldState(field, state, message = '') {
    field.classList.remove('field-error', 'field-success', 'field-neutral');
    field.classList.add(`field-${state}`);
    switch(state) {
        case 'error':
            field.style.borderColor = '#f44336';
            break;
        case 'success':
            field.style.borderColor = '#4CAF50';
            break;
        case 'neutral':
        default:
            field.style.borderColor = '#e0e0e0';
            break;
    }
    
    showFieldMessage(field, message, state);
}

function showFieldMessage(field, message, type) {
    let messageElement = field.parentNode.querySelector('.field-message');
    if (message) {
        if (!messageElement) {
            messageElement = document.createElement('small');
            messageElement.className = 'field-message';
            field.parentNode.appendChild(messageElement);
        }
        
        messageElement.textContent = message;
        messageElement.className = `field-message field-message-${type}`;
    } else if (messageElement) {
        messageElement.remove();
    }
}

function initUnsavedChangesDetection() {
    const form = document.querySelector('.profile-form');
    if (!form) return;
    form.addEventListener('input', function() {
        hasUnsavedChanges = true;
        updateSaveButtonState();
    });
    
    form.addEventListener('change', function() {
        hasUnsavedChanges = true;
        updateSaveButtonState();
    });
    
    form.addEventListener('submit', function() {
        hasUnsavedChanges = false;
    });
    
    const resetBtn = form.querySelector('button[type="reset"]');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            if (!confirmReset()) {
                e.preventDefault();
                return false;
            }
            
            hasUnsavedChanges = false;
            updateSaveButtonState();
            
            setTimeout(() => {
                const fields = form.querySelectorAll('input, textarea');
                fields.forEach(field => {
                    setFieldState(field, 'neutral');
                });
            }, 100);
        });
    }
    
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'Aveti modificari nesalvate. Sigur doriti sa parasiti pagina?';
            return e.returnValue;
        }
    });
    
    console.log('Unsaved changes detection initialized');
}
function updateSaveButtonState() {
    const saveBtn = document.querySelector('.btn-primary');
    if (!saveBtn) return;
    
    if (hasUnsavedChanges) {
        saveBtn.style.animation = 'pulse 2s infinite';
        saveBtn.title = 'Aveti modificari nesalvate';
    } else {
        saveBtn.style.animation = 'none';
        saveBtn.title = '';
    }
}

function initConfirmations() {
    window.confirmReset = function() {
        return confirm('Sigur doriti sa resetati formularul? Toate modificarile vor fi pierdute.');
    };
    
    window.confirmDeleteAccount = async function() {
        const confirmation1 = confirm('⚠️ ATENTIE: Stergerea contului este PERMANENTA!\n\nToate datele dumneavoastra vor fi pierdute definitiv:\n• Istoric depozitari\n• Rapoarte create\n• Statistici personale\n\nSigur doriti sa continuati?');
        if (!confirmation1) return false;
        const confirmation2 = confirm('Confirmati din nou ca doriti sa va stergeti contul?\n\nAceasta actiune NU POATE FI ANULATA!');
        if (!confirmation2) return false;
        const userName = document.getElementById('full_name').value;
        const finalConfirmation = prompt(`Pentru a finaliza stergerea, tastati numele dumneavoastra complet:\n"${userName}"`);
        if (finalConfirmation !== userName) {
            alert('Numele nu coincide. Stergerea a fost anulata.');
            return false;
        }
        try {
            const response = await fetch('../api/delete_account.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();
            if (data.success) {
                alert('Contul a fost sters cu succes. Veti fi delogat.');
                window.location.href = 'home.php?deleted=1';
            } else {
                alert('A aparut o eroare la stergerea contului!');
            }
        } catch (e) {
            alert('A aparut o eroare la stergerea contului!');
        }
        return false;
    };
    
    console.log('Confirmations initialized');
}

function initTooltips() {
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.title = 'Username-ul va fi vizibil in rapoartele publice';
    }
    
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.title = 'Email-ul este folosit pentru autentificare si notificari';
    }
    
    const phoneField = document.getElementById('phone');
    if (phoneField) {
        phoneField.title = 'Telefonul este optional si poate fi folosit pentru notificari urgente';
    }
    
    console.log('Tooltips initialized');
}

function initAnimations() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('mousedown', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        btn.addEventListener('mouseup', function() {
            this.style.transform = '';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
    
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
    });
    
    console.log('Animations initialized');
}


function validateCompleteForm() {
    const form = document.querySelector('.profile-form');
    if (!form) return false;
    
    let isValid = true;
    const errors = [];
    
    // Validam fiecare camp
    const fullNameField = document.getElementById('full_name');
    if (fullNameField && !validateFullName(fullNameField)) {
        isValid = false;
        errors.push('Numele complet nu este valid');
    }
    
    const usernameField = document.getElementById('username');
    if (usernameField && !validateUsername(usernameField)) {
        isValid = false;
        errors.push('Username-ul nu este valid');
    }
    
    const emailField = document.getElementById('email');
    if (emailField && !validateEmail(emailField)) {
        isValid = false;
        errors.push('Email-ul nu este valid');
    }
    
    const phoneField = document.getElementById('phone');
    if (phoneField && phoneField.value.trim() && !validatePhone(phoneField)) {
        isValid = false;
        errors.push('Telefonul nu este valid');
    }
    
    // Afisam erorile daca exista
    if (!isValid) {
        showNotification('Va rugam sa corectati erorile din formular:\n' + errors.join('\n'), 'error');
        return false;
    }
    
    return true;
}


function isValidEmailFormat(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhoneFormat(phone) {
    const phoneRegex = /^(\+4|0040|0)[0-9]{9}$|^(\+|00)[1-9][0-9]{7,14}$/;
    const cleanPhone = phone.replace(/[\s\-()]/g, '');
    return phoneRegex.test(cleanPhone);
}

function sanitizeInput(input) {
    return input.trim().replace(/[<>\"']/g, '');
}

function initAutoSave() {
    const form = document.querySelector('.profile-form');
    if (!form) return;
    
    let saveTimeout;
    
    function saveToLocalStorage() {
        try {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            localStorage.setItem('ecomanager_profile_draft', JSON.stringify(data));
            console.log('Profile draft saved');
        } catch (e) {
            console.log('Could not save profile draft:', e);
        }
    }
    
    function loadFromLocalStorage() {
        try {
            const draft = localStorage.getItem('ecomanager_profile_draft');
            if (draft) {
                const data = JSON.parse(draft);
                Object.keys(data).forEach(key => {
                    const field = form.querySelector(`[name="${key}"]`);
                    if (field && !field.value) {
                        field.value = data[key];
                    }
                });
                console.log('Profile draft loaded');
            }
        } catch (e) {
            console.log('Could not load profile draft:', e);
        }
    }
    
    function clearDraft() {
        try {
            localStorage.removeItem('ecomanager_profile_draft');
            console.log('Profile draft cleared');
        } catch (e) {
            console.log('Could not clear profile draft:', e);
        }
    }
    
    loadFromLocalStorage();
    form.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(saveToLocalStorage, 2000);
    });
    form.addEventListener('submit', function() {
        setTimeout(function() {
            // Verificam daca nu sunt erori in pagina
            if (!document.querySelector('.message.error')) {
                clearDraft();
            }
        }, 100);
    });
}

async function checkUsernameAvailability(username) {
    try {
        // aici s ar face un request catre server pentru verificarea username-ului
        // const response = await fetch('/api/check-username', {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify({ username })
        // });
        // return await response.json();
        
        return new Promise(resolve => {
            setTimeout(() => {
                resolve({ available: true });
            }, 500);
        });
    } catch (error) {
        console.error('Error checking username availability:', error);
        return { available: true, error: true };
    }
}

async function checkEmailAvailability(email) {
    try {
        return new Promise(resolve => {
            setTimeout(() => {
                resolve({ available: true });
            }, 500);
        });
    } catch (error) {
        console.error('Error checking email availability:', error);
        return { available: true, error: true };
    }
}
function initUserExperienceEnhancements() {
    const firstErrorField = document.querySelector('.field-error');
    if (firstErrorField) {
        firstErrorField.focus();
        firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    const form = document.querySelector('.profile-form');
    if (form) {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('.btn-primary');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Se salveaza...';
                submitBtn.classList.add('loading');
            }
        });
    }
}
function initAdditionalFeatures() {
    initAutoSave();
    initUserExperienceEnhancements();
    
    const usernameField = document.getElementById('username');
    if (usernameField) {
        let usernameTimeout;
        usernameField.addEventListener('input', function() {
            clearTimeout(usernameTimeout);
            usernameTimeout = setTimeout(async () => {
                if (this.value.length >= 3) {
                    const result = await checkUsernameAvailability(this.value);
                    if (!result.available && !result.error) {
                        setFieldState(this, 'error', 'Username-ul este deja folosit');
                    }
                }
            }, 1000);
        });
    }
    
}
window.addEventListener('load', function() {
    initAdditionalFeatures();
});

window.ProfileEditor = {
    validateCompleteForm,
    showNotification,
    confirmDeleteAccount: function() {
        return window.confirmDeleteAccount();
    },
    confirmReset: function() {
        return window.confirmReset();
    }
};

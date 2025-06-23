let currentTab = 'users';
let isProcessing = false;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin Panel JavaScript loaded successfully!');
    initTabNavigation();
    attachUserRoleListeners();
    attachUserDeleteListeners();
    loadSavedPreferences();
    setTimeout(initAutoSave, 500);
});


function switchAdminTab(tabName) {
    if (!['users', 'locations', 'system'].includes(tabName)) {
        console.error('Tab invalid:', tabName);
        return;
    }
    document.querySelectorAll('.admin-tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    const selectedTab = document.getElementById(tabName + '-tab');
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    const buttons = document.querySelectorAll('.admin-tab-btn');
    buttons.forEach((btn, index) => {
        if ((index === 0 && tabName === 'users') ||
            (index === 1 && tabName === 'locations') ||
            (index === 2 && tabName === 'system')) {
            btn.classList.add('active');
        }
    });
    
    currentTab = tabName;
    try {
        localStorage.setItem('ecomanager_admin_active_tab', tabName);
    } catch (e) {
        console.log('Nu s-a putut salva tab-ul activ:', e);
    }
    
    console.log('Switched to tab:', tabName);
}

function attachUserRoleListeners() {
    document.querySelectorAll('.role-select').forEach(select => {
        select.addEventListener('change', function() {
            if (isProcessing) return;
            const userId = this.getAttribute('data-user-id');
            const newRole = this.value;
            const oldRole = this.getAttribute('data-old-role') || this.querySelector('option[selected]')?.value;
            if (newRole === oldRole) return;
            if (!confirm(`Sigur doriți să schimbați rolul acestui utilizator în "${getRoleDisplayName(newRole)}"?`)) {
                this.value = oldRole;
                return;
            }
            updateUserRole(userId, newRole, this);
        });
        select.setAttribute('data-old-role', select.value);
    });
}

function attachUserDeleteListeners() {
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (isProcessing) return;
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            if (!confirm(`Sigur doriți să ștergeți utilizatorul "${userName}"?`)) return;
            deleteUser(userId, userName, this);
        });
    });
}

function getRoleDisplayName(role) {
    switch (role) {
        case 'citizen': return 'Cetățean';
        case 'staff': return 'Staff';
        case 'admin': return 'Admin';
        default: return role;
    }
}

function updateUserRole(userId, newRole, selectElement) {
    if (isProcessing) return;
    isProcessing = true;
    selectElement.disabled = true;
    const formData = new FormData();
    formData.append('ajax_action', 'update_user_role');
    formData.append('user_id', userId);
    formData.append('new_role', newRole);
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            setTimeout(() => window.location.reload(), 1200);
        } else {
            selectElement.value = selectElement.getAttribute('data-old-role');
        }
    })
    .catch(() => console.error('Eroare de comunicare cu serverul!'))
    .finally(() => {
        isProcessing = false;
        selectElement.disabled = false;
    });
}

function deleteUser(userId, userName, buttonElement) {
    if (isProcessing) return;
    if (!confirm(`ATENȚIE: Doriti sa stergeti definitiv utilizatorul "${userName}"?\n\nAceasta actiune nu poate fi anulata!`)) {
        return;
    }
    if (!confirm('Confirmati din nou stergerea? Toate datele asociate vor fi pierdute definitiv!')) {
        return;
    }
    isProcessing = true;
    buttonElement.disabled = true;
    buttonElement.textContent = '⏳ Șterg...';
    const formData = new FormData();
    formData.append('ajax_action', 'delete_user');
    formData.append('user_id', userId);
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = buttonElement.closest('tr');
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '0';
            setTimeout(() => {
                row.remove();
            }, 300);
        } else {
            buttonElement.disabled = false;
            buttonElement.textContent = '🗑️ sterge';
        }
    })
    .catch(error => {
        console.error('Eroare la ștergerea utilizatorului:', error);
        buttonElement.disabled = false;
        buttonElement.textContent = '🗑️ sterge';
    })
    .finally(() => {
        isProcessing = false;
    });
}

function initLocationManagement() {
    document.querySelectorAll('.edit-location-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const locationId = this.getAttribute('data-location-id');
        });
    });
    document.querySelectorAll('.delete-location-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (isProcessing) return;
            const locationId = this.getAttribute('data-location-id');
            const locationName = this.getAttribute('data-location-name');
            deleteLocation(locationId, locationName, this);
        });
    });
}

function deleteLocation(locationId, locationName, buttonElement) {
    if (isProcessing) return;
    
    const confirmMessage = `ATENTIE: Doriti sa stergeti definitiv locatia "${locationName}"?\n\nAceasta actiune nu poate fi anulata!`;
    
    if (!confirm(confirmMessage)) {
        return;
    }

    
    isProcessing = true;
    buttonElement.disabled = true;
    buttonElement.textContent = '⏳ sterg...';
    
    const formData = new FormData();
    formData.append('ajax_action', 'delete_location');
    formData.append('location_id', locationId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            
            const row = buttonElement.closest('tr');
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                row.remove();
                updateLocationStats();
            }, 300);
        } else {
            buttonElement.disabled = false;
            buttonElement.textContent = '🗑️ sterge';
        }
    })
    .catch(error => {
        console.error('Eroare la stergerea locatiei:', error);
        buttonElement.disabled = false;
        buttonElement.textContent = '🗑️ sterge';
    })
    .finally(() => {
        isProcessing = false;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addLocationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (window.isProcessing) return false;
            window.isProcessing = true;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Adaug...';
            const formData = new FormData(form);
            formData.append('ajax_action', 'add_location');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(async r => {
                const text = await r.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    return;
                }
                if (data.success) {
                    form.reset();
                    setTimeout(() => window.location.reload(), 1200);
                }
            })
            .catch(() => {
            })
            .finally(() => {
                window.isProcessing = false;
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
            return false;
        });
    }
    document.querySelectorAll('.delete-location-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (window.isProcessing) return;
            const locationId = this.getAttribute('data-location-id');
            const locationName = this.getAttribute('data-location-name');
            if (!confirm(`Sigur doriti sa eliminati punctul "${locationName}"?`)) return;
            window.isProcessing = true;
            this.disabled = true;
            this.textContent = '⏳ Elimin...';
            const formData = new FormData();
            formData.append('ajax_action', 'delete_location');
            formData.append('location_id', locationId);
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const card = this.closest('.location-row');
                    card.style.transition = 'all 0.3s';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 300);
                } else {
                    this.disabled = false;
                    this.textContent = '🗑️ Elimina';
                }
            })
            .catch(() => {
                this.disabled = false;
                this.textContent = '🗑️ Elimina';
            })
            .finally(() => {
                window.isProcessing = false;
            });
        });
    });
});


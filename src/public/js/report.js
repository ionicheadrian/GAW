function setActiveTabButton(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(`switchTab('${tabName}')`)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    const selectedTab = document.getElementById(tabName + '-tab');
    if (selectedTab) selectedTab.classList.add('active');
    setActiveTabButton(tabName);
    try { localStorage.setItem('ecomanager_active_tab', tabName); } catch (e) {}

    if (tabName === 'problem') {
        switchActivityTab('reports');
    } else if (tabName === 'deposit') {
        switchActivityTab('deposits');
    }
}

function setActiveActivityTabButton(tabName) {
    document.querySelectorAll('.activity-tab-btn').forEach(btn => {
        if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(`switchActivityTab('${tabName}')`)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

function switchActivityTab(tabName) {
    document.querySelectorAll('.activity-content').forEach(content => content.classList.remove('active'));
    const selectedContent = document.getElementById(tabName + '-activity');
    if (selectedContent) selectedContent.classList.add('active');
    setActiveActivityTabButton(tabName);
    try { localStorage.setItem('ecomanager_active_activity_tab', tabName); } catch (e) {}
}

function updateCapacityInfo() {
    const locationSelect = document.getElementById('location_id');
    const capacityInfo = document.getElementById('capacity-info');

    if (!locationSelect || !capacityInfo) return;

    const selectedOption = locationSelect.options[locationSelect.selectedIndex];

    if (selectedOption.value === '') { 
        capacityInfo.style.display = 'none'; return; 
    }
    const menajerData = selectedOption.getAttribute('data-menajer') || '0/0';
    const hartieData = selectedOption.getAttribute('data-hartie') || '0/0';
    const plasticData = selectedOption.getAttribute('data-plastic') || '0/0';
    const [menajerCurrent, menajerCapacity] = menajerData.split('/').map(parseFloat);
    const [hartieCurrent, hartieCapacity] = hartieData.split('/').map(parseFloat);
    const [plasticCurrent, plasticCapacity] = plasticData.split('/').map(parseFloat);
    const menajerStatus = updateCapacityBar('menajer', menajerCurrent, menajerCapacity);
    const hartieStatus = updateCapacityBar('hartie', hartieCurrent, hartieCapacity);
    const plasticStatus = updateCapacityBar('plastic', plasticCurrent, plasticCapacity);

    window.capacityStatus = { 1: menajerStatus, 2: hartieStatus, 3: plasticStatus };
    updateWasteCategoryOptions();
    capacityInfo.style.display = 'block';
    capacityInfo.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function updateWasteCategoryOptions() {
    const categorySelect = document.getElementById('waste_category_deposit');
    if (!categorySelect || !window.capacityStatus) return;
    Array.from(categorySelect.options).forEach(option => {
        option.disabled = false; option.style.color = ''; option.title = '';
    });
    Object.keys(window.capacityStatus).forEach(categoryId => {
        const status = window.capacityStatus[categoryId];
        const option = categorySelect.querySelector(`option[value="${categoryId}"]`);
        if (option && status.isFull) {
            option.disabled = true;
            option.style.color = '#f44336';
            option.title = 'Containerul este plin! Alegeti o alta locatie.';
            if (!option.textContent.includes('(PLIN)')) option.textContent += ' (PLIN)';
        }
    });
}

function updateCapacityBar(type, current, capacity) {
    const fillElement = document.getElementById(type + '-fill');
    const textElement = document.getElementById(type + '-text');
    if (!fillElement || !textElement) return;
    const percentage = capacity > 0 ? Math.min((current / capacity) * 100, 100) : 0;
    fillElement.style.width = percentage + '%';
    textElement.textContent = `${current.toFixed(1)}/${capacity.toFixed(1)} kg`;
    fillElement.classList.remove('warning', 'danger', 'full');
    if (percentage >= 100) { fillElement.classList.add('full'); fillElement.style.background = 'linear-gradient(90deg, #f44336, #d32f2f)'; }
    else if (percentage >= 90) fillElement.classList.add('danger');
    else if (percentage >= 75) fillElement.classList.add('warning');
    if (percentage >= 100) {
        fillElement.style.animation = 'pulse 1s infinite';
        textElement.style.color = '#f44336';
        textElement.style.fontWeight = 'bold';
    } else {
        fillElement.style.animation = 'none';
        textElement.style.color = '#666';
        textElement.style.fontWeight = '600';
    }
    return { percentage, isFull: percentage >= 100 };
}

function addPulseAnimation() {
    if (!document.getElementById('pulse-animation-style')) {
        const style = document.createElement('style');
        style.id = 'pulse-animation-style';
        style.textContent = `@keyframes pulse {0%{opacity:1;}50%{opacity:0.7;}100%{opacity:1;}}`;
        document.head.appendChild(style);
    }
}

// function showNotification(message, type = 'info', duration = 5000) {
//     const notification = document.createElement('div');
//     notification.className = `notification notification-${type}`;
//     notification.innerHTML = `<div class="notification-content"><span class="notification-icon">${type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️'}</span><span class="notification-text">${message}</span><button class="notification-close" onclick="this.parentElement.parentElement.remove()">✕</button></div>`;
//     if (!document.getElementById('notification-styles')) {
//         const style = document.createElement('style');
//         style.id = 'notification-styles';
//         style.textContent = `.notification{position:fixed;top:20px;right:20px;max-width:400px;padding:16px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:10000;animation:slideInNotification 0.3s ease-out;}.notification-error{background:#ffebee;border-left:4px solid #f44336;color:#c62828;}.notification-success{background:#e8f5e9;border-left:4px solid #4caf50;color:#2e7d32;}.notification-info{background:#e3f2fd;border-left:4px solid #2196f3;color:#1976d2;}.notification-content{display:flex;align-items:center;gap:10px;}.notification-icon{font-size:1.2rem;}.notification-text{flex:1;font-size:0.9rem;}.notification-close{background:none;border:none;font-size:1rem;cursor:pointer;opacity:0.7;padding:0;width:20px;height:20px;display:flex;align-items:center;justify-content:center;}.notification-close:hover{opacity:1;}@keyframes slideInNotification{from{transform:translateX(100%);opacity:0;}to{transform:translateX(0);opacity:1;}}`;
//         document.head.appendChild(style);
//     }
//     document.body.appendChild(notification);
//     setTimeout(() => {
//         if (notification.parentElement) {
//             notification.style.animation = 'slideInNotification 0.3s ease-out reverse';
//             setTimeout(() => notification.remove(), 300);
//         }
//     }, duration);
// }

function initDepositValidation() {
    const depositForm = document.querySelector('form[action=""] input[value="deposit_waste"]');
    if (!depositForm) return;
    const form = depositForm.closest('form');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        const locationId = document.getElementById('location_id').value;
        const wasteCategory = document.getElementById('waste_category_deposit').value;
        const quantity = parseFloat(document.getElementById('quantity').value);
        let errors = [];
        if (!locationId) errors.push('Va rugam sa selectati un punct de colectare!');
        if (!wasteCategory) errors.push('Va rugam sa selectati tipul de deseuri!');
        if (!quantity || quantity <= 0) errors.push('Cantitatea trebuie sa fie mai mare decat 0!');
        else if (quantity > 50) errors.push('Cantitatea pare prea mare! Maximum 50kg per depozitare.');
        if (locationId && wasteCategory && quantity > 0) {
            const locationSelect = document.getElementById('location_id');
            const selectedOption = locationSelect.options[locationSelect.selectedIndex];
            if (window.capacityStatus && window.capacityStatus[wasteCategory]) {
                const status = window.capacityStatus[wasteCategory];
                if (status.isFull) errors.push('Containerul pentru categoria selectata este deja plin! Va rugam sa alegeti o alta locatie.');
                else {
                    const categoryMap = { '1': 'data-menajer', '2': 'data-hartie', '3': 'data-plastic' };
                    const dataAttr = categoryMap[wasteCategory];
                    if (dataAttr) {
                        const capacityData = selectedOption.getAttribute(dataAttr) || '0/0';
                        const [current, capacity] = capacityData.split('/').map(parseFloat);
                        if (capacity > 0) {
                            const availableSpace = capacity - current;
                            if (quantity > availableSpace) errors.push(`Cantitatea introdusa (${quantity}kg) depaseste spatiul disponibil (${availableSpace.toFixed(1)}kg) pentru aceasta categorie!`);
                        }
                    }
                }
            }
        }
        if (errors.length > 0) { showNotification(errors.join('\n'), 'error', 8000); e.preventDefault(); return false; }
        if (!confirm(`Confirmati depozitarea de ${quantity}kg de deseuri la locatia selectata?`)) { e.preventDefault(); return false; }
    });
}

function initRealTimeValidation() {
    const titleField = document.getElementById('title');
    if (titleField) {
        titleField.addEventListener('input', function() {
            const length = this.value.trim().length;
            if (length > 0 && length < 5) this.style.borderColor = '#f44336';
            else if (length >= 5) this.style.borderColor = '#4CAF50';
            else this.style.borderColor = '#e0e0e0';
        });
    }
    const descriptionField = document.getElementById('description');
    if (descriptionField) {
        descriptionField.addEventListener('input', function() {
            const length = this.value.trim().length;
            if (length > 0 && length < 10) this.style.borderColor = '#f44336';
            else if (length >= 10) this.style.borderColor = '#4CAF50';
            else this.style.borderColor = '#e0e0e0';
        });
    }
    const quantityField = document.getElementById('quantity');
    if (quantityField) {
        quantityField.addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (value > 0 && value <= 50) this.style.borderColor = '#4CAF50';
            else if (value > 50) this.style.borderColor = '#FF9800';
            else this.style.borderColor = '#f44336';
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    addPulseAnimation();
    switchTab('deposit');
    switchActivityTab('deposits');
    try { const savedTab = localStorage.getItem('ecomanager_active_tab'); if (savedTab) switchTab(savedTab); } catch (e) {}
    try { const savedActivityTab = localStorage.getItem('ecomanager_active_activity_tab'); if (savedActivityTab) switchActivityTab(savedActivityTab); } catch (e) {}
    initDepositValidation();
    initRealTimeValidation();
});
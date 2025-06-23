// functionalitate pt GPS si locatia clientului
function initGPSFunctionality() {
    // cautam elementul cu idul de mai jos
    const getLocationBtn = document.getElementById('get-location');
    if (!getLocationBtn) return;

    // am adaugat un event listener pentru click
    // care o sa faca urmatoarele:
    // schimba textul butonului pt diferite stateuri
    // ia locatia
    // in caz de succes pune deja datele in campurile cu coordonate
    getLocationBtn.addEventListener('click', function () {
        if (navigator.geolocation) {
            this.textContent = '\ud83d\udccd Obtinand locatia...';
            this.disabled = true;

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    // succes pentru locatie
                    // punem datele in elemente SI LE FORMATAM DIRECT AICI
                    document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
                    document.getElementById('longitude').value = position.coords.longitude.toFixed(6);

                    // actualizam stateul butonului
                    getLocationBtn.textContent = '\u2705 Locatia obtinuta';
                    getLocationBtn.disabled = false;

                    //
                    // TODO: incearca obtinerea adresei prin coordonate (reverse geocoding)
                    // 
                },
                function (error) {
                    // in caz de eroare (acces refuzat, nu putem gasi locatia, sau alte erori unexpected)
                    let errorMessage = 'Eroare la obtinerea locatiei: ';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage += 'Accesul la locatie a fost refuzat.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Informatiile despre locatie nu sunt disponibile.';
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'Cererea pentru locatie a expirat.';
                            break;
                        default:
                            errorMessage += 'A aparut o eroare necunoscuta.';
                            break;
                    }
                    alert(errorMessage);
                    getLocationBtn.textContent = '\ud83d\udcf1 Foloseste GPS';
                    getLocationBtn.disabled = false;
                }
            );
        } else {
            alert('Browser-ul tau nu suporta Geolocation.');
            getLocationBtn.textContent = '\ud83d\udcf1 Foloseste GPS';
            getLocationBtn.disabled = false;
        }
    });
}

// validarea formularului (client side :P)
function initFormValidation() {
    const reportForm = document.querySelector('.report-form');
    if (!reportForm) return;

    reportForm.addEventListener('submit', function (e) {
        // optinem toate datele (titlu, descriptie, adresa etc)
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const category = document.getElementById('waste_category').value;
        const address = document.getElementById('address').value.trim();
        const latitude = document.getElementById('latitude').value;
        const longitude = document.getElementById('longitude').value;

        let errors = [];

        if (title.length < 5) {
            errors.push('Titlul trebuie sa aiba cel putin 5 caractere!');
        }
        if (description.length < 10) {
            errors.push('Descrierea trebuie sa aiba cel putin 10 caractere!');
        }
        if (!category) {
            errors.push('Va rugam sa selectati o categorie de deseuri!');
        }
        if (!address) {
            errors.push('Adresa este obligatorie!');
        }

        if (!latitude || !longitude || parseFloat(latitude) === 0 || parseFloat(longitude) === 0) {
            errors.push('Va rugam sa introduceti locatia (coordonatele GPS)!');
            //
            // TODO: VERIFICARE DACA COORDONATELE SUNT SAU NU IN RO (fancy)
            //
        }

        // daca exista erori, nu mai trimitem datele la bd
        // pur si simplu le semnalam
        if (errors.length > 0) {
            alert('Erori de validare:\n\n' + errors.join('\n'));
            e.preventDefault();
            return false;
        }
    });
}

// validarea timp real a datelor (client side)
function initRealTimeValidation() {
    // apucam elementele prin id
    const titleField = document.getElementById('title');
    const descriptionField = document.getElementById('description');

    if (titleField) {
        titleField.addEventListener('input', function () {
            const length = this.value.trim().length;
            if (length > 0 && length < 5) {
                this.style.borderColor = '#f44336';
            } else if (length >= 5) {
                this.style.borderColor = '#4CAF50';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });
    }

    if (descriptionField) {
        descriptionField.addEventListener('input', function () {
            const length = this.value.trim().length;
            if (length > 0 && length < 10) {
                this.style.borderColor = '#f44336';
            } else if (length >= 10) {
                this.style.borderColor = '#4CAF50';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });
    }
}

// reset button
function initResetFunctionality() {
    // apucam elementrul button cu typeul de reset (nu mai luam dupa id :P)
    const resetBtn = document.querySelector('button[type="reset"]');
    if (!resetBtn) return;

    resetBtn.addEventListener('click', function (e) {
        // human error prevention cn stie poate a apasat gresit 
        if (!confirm('Sigur doriti sa resetati formularul? Toate datele introduse vor fi pierdute.')) {
            e.preventDefault();
            return false;
        }

        // dupa ce a confirmat resetam
        const addressField = document.getElementById('address');
        if (addressField) {
            addressField.placeholder = 'Strada, numarul, cartierul';
        }
        document.getElementById('title').style.borderColor = '#e0e0e0';
        document.getElementById('description').style.borderColor = '#e0e0e0';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initGPSFunctionality();
    initFormValidation();
    initResetFunctionality();
    initRealTimeValidation();
    //autofocus pe primul field al formului
    const firstField = document.getElementById('title');
    if (firstField) {
        firstField.focus();
    }
});
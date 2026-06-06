function getElementSafely(selector, elementType = 'any') {
    const element = document.querySelector(selector);

    if (element) {
        if (elementType === 'any' || element.nodeName.toLowerCase() === elementType.toLowerCase()) {
            return element;
        } else {
            console.log(`Element with selector '${selector}' is not of type '${elementType}'.`);
        }
    } else {
        console.log(`Element with selector '${selector}' not found in the DOM.`);
    }

    return null;
}


document.addEventListener('DOMContentLoaded', (event) => {

    const errorMessages = {

        "required_givenname": {
            "default": "Das Feld \"vorname\" muss ausgefüllt werden!",
            "english": "The field \"given name\" is required!"
        },
        "required_familyname": {
            "default": "Das Feld \"nachname\" muss ausgefüllt werden!",
            "english": "The field \"family name\" is required!"
        },
        "required_emailaddress": {
            "default": "Das Feld \"e-mail-adresse\" muss ausgefüllt werden!",
            "english": "The field \"email address\" is required!"
        },
        "wrong_emailaddress": {
            "default": "Die \"e-mail-adresse\" ist nicht gültig!",
            "english": "The \"email address\" is not valid!"
        },
        "required_subject": {
            "default": "Das Feld \"betreff\" muss ausgefüllt werden!",
            "english": "The field \"subject\" is required!"
        },
        "required_message": {
            "default": "Das Feld \"nachricht\" muss ausgefüllt werden!",
            "english": "The field \"message\" is required!"
        },
        "required_privacyCheckbox": {
            "default": "Sie müssen die \"datenschutzerklärung\" akzeptieren!",
            "english": "You must accept the \"privacy policy\"!"
        },
        "json_parse_error": {
            "default": "Failed to parse JSON from server response.",
            "english": "Failed to parse JSON from server response."
        },
        "server_error": {
            "default": "Server Error: {error}",
            "english": "Server Error: {error}"
        },
        "form_success": {
            "default": "Formular erfolgreich abgeschickt",
            "english": "Form successfully submitted!"
        },
        "too_many_files": {
            "default": "Sie können maximal {maxFileCount} Dateien hochladen.",
            "english": "You can upload a maximum of {maxFileCount} files."
        },
        "totalSizeExceeded": {
            "default": "Die Gesamtgröße aller Dateien überschreitet die maximal zulässige Größe von {maxTotalSizeMB}MB.",
            "english": "The total size of all files exceeds the maximum allowed size of {maxTotalSizeMB}MB."
        },
        "invalid_extension": {
            "default": "Die Datei {filename} hat eine ungültige Erweiterung. Zulässige Erweiterungen sind: {allowedExtensions}.",
            "english": "File {filename} has an invalid extension. Allowed extensions are: {allowedExtensions}."
        }

    };

    const infoalert = document.getElementById('infoalert');
    const submitButton = document.getElementById('sendform');
    const formElement = document.getElementById('simpleform');

    if (!infoalert || !submitButton || !formElement) {
        console.error('SimpleForm: required elements missing (#infoalert, #sendform, or #simpleform)');
        return;
    }

    const recaptchaEl = document.getElementById('grecaptcha');
    const ReCaptchaSiteKey = recaptchaEl?.dataset.sitekey;

    function showInfoAlert(html) {
        infoalert.innerHTML = html;
        infoalert.classList.remove('hidden');
        infoalert.style.display = '';
        infoalert.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function hideInfoAlert() {
        infoalert.innerHTML = '';
        infoalert.classList.add('hidden');
        infoalert.style.display = 'none';
    }

    const filesInput = getElementSafely("#simpleform input[type='file']", 'input');

    let maxTotalFileSize = 0;
    let allowedFileCount = 0;
    let allowedExtensions = [];

    if (filesInput) {
        maxTotalFileSize = parseInt(filesInput.getAttribute('data-maxtotalfilesize'), 10) || 0;
        allowedFileCount = parseInt(filesInput.getAttribute('data-maxfileamount'), 10) || 0;
        allowedExtensions = (filesInput.getAttribute('data-allowedextensions') || '').split(' ').filter(Boolean);
    }

    const loadingOverlay = document.getElementById('loadingOverlay');
    const pageLanguage = formElement.lang;

    window.onerror = function(message, source, lineno) {
        console.error('An error occurred:', message, 'at line:', lineno, 'of source:', source);
        showInfoAlert('An error occurred: ' + message + ' at line: ' + lineno + ' of source: ' + source);
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        submitButton.disabled = false;
    };

    submitButton.addEventListener('click', handleForm);

    formElement.addEventListener('keypress', function(evt) {
        if (evt.keyCode == 13 && evt.target.tagName !== 'TEXTAREA') {
            evt.preventDefault();
            return false;
        }
    });


    async function handleForm(event) {

        event.preventDefault();

        let response = null;
        let serverErrorURL = null;

        try {

            console.log('About to validate...');
            validateForm();

            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
            }

            console.log('About to get token...');
            let token = await getRecaptchaToken();
            console.log('Token received:', token);

            console.log('About to send form data...');
            response = await sendFormData(token);
            console.log('Data sent successfully!');

            submitButton.disabled = false;

            if (response.errors && response.errors.length > 0) {
                let serverError = '';
                response.errors.forEach(err => {
                    let currentError = errorMessages['server_error'][pageLanguage].replace('{error}', err);
                    serverError += currentError + '<br>';
                });
                serverErrorURL = response.errorURL;
                throw new Error(serverError);
            }

            const successMessage = errorMessages['form_success'][pageLanguage];
            showInfoAlert(successMessage);

            if (response && response.successURL) {
                setTimeout(() => {
                    window.location.href = window.location.origin + response.successURL;
                }, 3000);
            }

        } catch (error) {
            showInfoAlert(error.message);
            console.log(error);
            submitButton.disabled = false;
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }

            if (serverErrorURL) {
                setTimeout(() => {
                    window.location.href = window.location.origin + serverErrorURL;
                }, 3000);
            }
        }

    }

    function validateForm() {

        hideInfoAlert();
        let errorList = [];

        submitButton.disabled = true;

        const fields = document.querySelectorAll('#simpleform input, #simpleform textarea');
        const privacyCheckbox = document.getElementById('privacyCheckbox');

        fields.forEach((field) => {
            if (field.hasAttribute('required') && !field.value) {
                const errorKey = field.getAttribute('data-error-key-required');
                const errorMessage = errorMessages[errorKey][pageLanguage];
                errorList.push(errorMessage);
            } else if (field.type === 'email' && field.value && !validateEmail(field.value)) {
                const errorKey = field.getAttribute('data-error-key-wrong');
                const errorMessage = errorMessages[errorKey][pageLanguage];
                errorList.push(errorMessage);
            }
        });

        if (privacyCheckbox && !privacyCheckbox.checked) {
            const errorKey = privacyCheckbox.getAttribute('data-error-key-required');
            const errorMessage = errorMessages[errorKey][pageLanguage];
            errorList.push(errorMessage);
        }

        let totalSize = 0;

        if (filesInput) {
            for (let i = 0; i < filesInput.files.length; i++) {
                totalSize += filesInput.files[i].size;
            }

            if (totalSize > maxTotalFileSize) {
                const readableFileSize = (maxTotalFileSize / (1024 * 1024)).toFixed(2);
                const errorMessage = errorMessages['totalSizeExceeded'][pageLanguage]
                    .replace('{maxTotalSizeMB}', readableFileSize);
                errorList.push(errorMessage);
            }

            if (filesInput.files.length > allowedFileCount) {
                const errorMessage = errorMessages['too_many_files'][pageLanguage]
                    .replace('{maxFileCount}', allowedFileCount);
                errorList.push(errorMessage);
            }

            for (let i = 0; i < filesInput.files.length; i++) {
                let file = filesInput.files[i];
                let fileExtension = file.name.slice(((file.name.lastIndexOf('.') - 1) >>> 0) + 2).toLowerCase();
                if (!allowedExtensions.includes(fileExtension)) {
                    const errorMessage = errorMessages['invalid_extension'][pageLanguage]
                        .replace('{filename}', file.name)
                        .replace('{allowedExtensions}', allowedExtensions.join(', '));
                    errorList.push(errorMessage);
                }
            }
        }

        if (errorList.length > 0) {
            throw new Error(errorList.join('<br>'));
        }

    }

    async function sendFormData(token) {
        const formData = new FormData(formElement);
        formData.set('captchaToken', token);

        const fetchResponse = await fetch('./', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const textData = await fetchResponse.text();

        if (fetchResponse.status === 500) {
            throw new Error('The server encountered an issue. Please try again later.');
        }

        try {
            return JSON.parse(textData);
        } catch (e) {
            const errorMessage = errorMessages['json_parse_error'][pageLanguage];
            throw new Error(errorMessage);
        }
    }


    function getRecaptchaToken() {
        return new Promise((resolve, reject) => {
            const TIMEOUT = 10000;

            const timer = setTimeout(() => {
                reject(new Error('ReCAPTCHA took too long to respond'));
            }, TIMEOUT);

            grecaptcha.ready(() => {
                grecaptcha.execute(ReCaptchaSiteKey, { action: 'submit' })
                    .then(token => {
                        clearTimeout(timer);
                        if (token) {
                            resolve(token);
                        } else {
                            reject(new Error('Received an empty ReCAPTCHA token'));
                        }
                    })
                    .catch(reject);
            });
        });
    }


    function validateEmail(email) {
        return email.includes('@') && email.includes('.');
    }


});

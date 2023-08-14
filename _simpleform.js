document.addEventListener('DOMContentLoaded', (event) => {
    
    const errorMessages = {

        "required_givenname": {
            "default": "Das Feld Vorname muss ausgefüllt werden!",
            "english": "The field First Name is required!"
        },
        "required_familyname": {
            "default": "Das Feld Nachname muss ausgefüllt werden!",
            "english": "The field Last Name is required!"
        },
        "required_emailaddress": {
            "default": "Das Feld E-Mail-Adresse muss ausgefüllt werden!",
            "english": "The field Email Address is required!"
        },
        "wrong_emailaddress": {
            "default": "Die Email-Adresse ist nicht gültig!",
            "english": "The Email Address is not valid!"
        },
        "required_subject": {
            "default": "Das Feld Betreff muss ausgefüllt werden!",
            "english": "The field Subject is required!"
        },
        "required_message": {
            "default": "Das Feld Nachricht muss ausgefüllt werden!",
            "english": "The field Message is required!"
        },
        "required_privacyCheckbox": {
            "default": "Sie müssen die Datenschutzerklärung akzeptieren!",
            "english": "You must accept the Privacy Policy!"
        },
        "json_parse_error": {
            "default": "[Translation for: 'Failed to parse JSON from server response.']",
            "english": "Failed to parse JSON from server response."
        },
        "server_error": {
            "default": "[Translation for: 'Server Error:'] {error}",
            "english": "Server Error: {error}"
        },
        "form_success": {
            "default": "[Translation for: 'Form successfully submitted!']",
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
            
    }
    
    const filesInput = document.querySelector("#simpleform input[type='file']");
    let maxTotalFileSize = parseInt(filesInput.getAttribute('data-maxtotalfilesize'));
    let allowedFileCount = parseInt(filesInput.getAttribute('data-maxfileamount')); 
    let allowedExtensions = filesInput.getAttribute('data-allowedextensions').split(" ");
    
    const submitButton = document.getElementById('sendform');

    // Access the 'lang' attribute of the <form> element
    const formElement = document.getElementById('simpleform');
    const pageLanguage = formElement.lang;

    document.getElementById('sendform').addEventListener('click', validateForm);
        
    document.querySelector("#simpleform").addEventListener('keydown', function(event) {
        // The key code for enter key is 13
        if (event.keyCode == 13) {
            // Prevent the default action
            event.preventDefault();
            return false;
        }
    });

    function validateForm(event) {

        event.preventDefault();

        let errorList = [];

        // Disable the submit button
        submitButton.disabled = true;

        const infoalert = document.getElementById('infoalert');

        let valid = true;
        infoalert.innerHTML = ''; // Clear the alert box at the start of each validation attempt.

        try {
            
            const fields = document.querySelectorAll("#simpleform input, #simpleform textarea");
            const privacyCheckbox = document.getElementById('privacyCheckbox');
        
            fields.forEach((field) => {
                if (field.hasAttribute('required') && !field.value) {
                    const errorKey = field.getAttribute('data-error-key-required');
                    const errorMessage = errorMessages[errorKey][pageLanguage];
                    // throw new Error(errorMessage);
                    errorList.push(errorMessage)
                }
                if (field.type === 'email' && !validateEmail(field.value)) {
                    const errorKey = field.getAttribute('data-error-key-wrong');
                    const errorMessage = errorMessages[errorKey][pageLanguage];
                    // throw new Error(errorMessage);
                    errorList.push(errorMessage)
                }
            });
        
            if (!privacyCheckbox.checked) {
                const errorKey = privacyCheckbox.getAttribute('data-error-key-required');
                const errorMessage = errorMessages[errorKey][pageLanguage];
                // throw new Error(errorMessage);
                errorList.push(errorMessage)
            }
        
            const filesInput = document.querySelector("#simpleform input[type='file']");
        
            let totalSize = 0;
            for (let i = 0; i < filesInput.files.length; i++) {
                totalSize += filesInput.files[i].size;
            }
            
            if (totalSize > maxTotalFileSize) {
                const readableFileSize = (maxTotalFileSize / (1024 * 1024)).toFixed(2); // Convert to MB for readability
                const errorMessage = errorMessages["totalSizeExceeded"][pageLanguage]
                    .replace("{maxTotalSizeMB}", readableFileSize);
                errorList.push(errorMessage);
            }
                        
            // Validate number of files
            if (filesInput.files.length > allowedFileCount) { 
                const errorMessage = errorMessages["too_many_files"][pageLanguage]
                    .replace("{maxFileCount}", allowedFileCount);
                errorList.push(errorMessage);
            }
                    
            // Validate file sizes and extensions
            for (let i = 0; i < filesInput.files.length; i++) {
                let file = filesInput.files[i];

                // Check file extension
                let fileExtension = file.name.slice(((file.name.lastIndexOf(".") - 1) >>> 0) + 2).toLowerCase();
                alert(allowedExtensions)
                alert(fileExtension)
                if (!allowedExtensions.includes(fileExtension)) {
                    const errorMessage = errorMessages["invalid_extension"][pageLanguage]
                        .replace("{filename}", file.name)
                        .replace("{allowedExtensions}", allowedExtensions.join(", "));
                    // throw new Error(errorMessage);
                    errorList.push(errorMessage);
                }
            }
            
            if (errorList.length > 0) {
                submitButton.disabled = false;
                throw new Error(errorList.join("; "));
            }
            
            sendFormData();
        
        } catch (error) {
            const individualErrors = error.message.split("; ");
            individualErrors.forEach(err => {
                infoalert.innerHTML += err + '<br>';
            });
            submitButton.disabled = false;
        }

    }


    function sendFormData() {
        let formData = new FormData(document.querySelector("#simpleform"));
    
        const grecaptcha = document.getElementById('grecaptcha');
        const ReCaptchaSiteKey = grecaptcha.dataset.sitekey;
    
        grecaptcha.ready(() =>
            grecaptcha.execute(ReCaptchaSiteKey, { action: 'submit' })
            .then(token => {
                formData.set("captchaToken", token);
    
                return fetch('./', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                });
            })
            .then(response => {
                console.log('Response Status:', response.status, response.statusText);
                console.log('Response Headers:', response.headers.get('Content-Type'));
    
                return response.text().then(textData => {
                    console.log('Raw Text Response:', textData);
                    try {
                        return JSON.parse(textData);
                    } catch (e) {
                        const errorMessage = errorMessages["json_parse_error"][pageLanguage];
                        throw new Error(errorMessage);
                    }
                });
            })
            .then(data => {
                const infoalert = document.getElementById('infoalert');
                if(data.redirectURL) {
                    console.log(data.redirectURL);
                    console.log(data.errors);
                    // window.location.href = window.location.origin + data.redirectURL;
                    // console.log("window.location.origin + data.redirectURL: " + window.location.origin + data.redirectURL)
                } else {
                    if(data.errors) {
                        console.error(data.errors);
                        data.errors.forEach(err => {
                            const serverError = errorMessages["server_error"][pageLanguage].replace("{error}", err);
                            infoalert.innerHTML += serverError + '<br>';
                        });
                    } else {
                        const successMessage = errorMessages["form_success"][pageLanguage];
                        infoalert.innerHTML = successMessage;
                        infoalert.style.display = 'inline-block';
                        console.log(data);
                    }
                }
                submitButton.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                submitButton.disabled = false;
            })
        );
    }


    function validateEmail(email) {
        return email.includes('@') && email.includes('.');
    }

});

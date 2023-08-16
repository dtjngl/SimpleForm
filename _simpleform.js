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

    const ReCaptchaSiteKey = grecaptcha.dataset.sitekey;

    const infoalert = document.getElementById('infoalert');

    infoalert.addEventListener('DOMSubtreeModified', function() {
        if (this.innerHTML.trim() !== "") {
            this.style.display = 'inline-block';
        } else {
            this.style.display = 'none';
        }
    });
    
    const filesInput = document.querySelector("#simpleform input[type='file']");
    let maxTotalFileSize = parseInt(filesInput.getAttribute('data-maxtotalfilesize'));
    let allowedFileCount = parseInt(filesInput.getAttribute('data-maxfileamount')); 
    let allowedExtensions = filesInput.getAttribute('data-allowedextensions').split(" ");
    
    const submitButton = document.getElementById('sendform');

    // Global error handler
    window.onerror = function(message, source, lineno, colno, error) {
        console.error('An error occurred:', message, 'at line:', lineno, 'of source:', source);
        infoalert.innerHTML = 'An error occurred: ' + message + ' at line: ' + lineno + ' of source: ' + source;
        // You can also handle the error or do any cleanup here
        document.getElementById('loadingOverlay').style.display = 'none';
        submitButton.disabled = false;
        console.log("Global error caught:", message);
    };

    // Access the 'lang' attribute of the <form> element
    const formElement = document.getElementById('simpleform');
    const pageLanguage = formElement.lang;

    document.getElementById('sendform').addEventListener('click', handleForm);
        
    document.querySelector("#simpleform").addEventListener("keypress", function(evt) {
        if (evt.keyCode == 13 && evt.target.tagName !== "TEXTAREA") {
            evt.preventDefault();
            return false;
        }
    });
    

    async function handleForm(event) {

        event.preventDefault();
        
        console.log("Starting form handler...");

        try {

            console.log("About to validate...");
            validateForm(); // If there's an error, it will throw and go to the catch block below
            
            console.log("About to get token...");
            let token = await getRecaptchaToken();
            console.log("Token received:", token);
            
            console.log("About to send form data...");
            let response = await sendFormData(token);
            console.log("Data sent successfully!");
            
            submitButton.disabled = false
            // Handle successful server response here
    
        } catch (error) {
            // Handle any errors here, either from validateForm, recaptcha or sendFormData
            infoalert.innerHTML = error.message;
            console.error(error.message)
            submitButton.disabled = false;
            document.getElementById('loadingOverlay').style.display = 'none';
        }

    }
    

    function validateForm(event) {

        infoalert.innerHTML = '';
        let errorList = [];

        // Disable the submit button
        submitButton.disabled = true;

        let valid = true;
            
        const fields = document.querySelectorAll("#simpleform input, #simpleform textarea");
        const privacyCheckbox = document.getElementById('privacyCheckbox');
    
        fields.forEach((field) => {
            if (field.hasAttribute('required') && !field.value) {
                const errorKey = field.getAttribute('data-error-key-required');
                const errorMessage = errorMessages[errorKey][pageLanguage];
                errorList.push(errorMessage)
            } else if (field.type === 'email' && !validateEmail(field.value)) {
                const errorKey = field.getAttribute('data-error-key-wrong');
                const errorMessage = errorMessages[errorKey][pageLanguage];
                errorList.push(errorMessage)
            }
        });
    
        if (!privacyCheckbox.checked) {
            const errorKey = privacyCheckbox.getAttribute('data-error-key-required');
            const errorMessage = errorMessages[errorKey][pageLanguage];
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
            if (!allowedExtensions.includes(fileExtension)) {
                const errorMessage = errorMessages["invalid_extension"][pageLanguage]
                    .replace("{filename}", file.name)
                    .replace("{allowedExtensions}", allowedExtensions.join(", "));
                errorList.push(errorMessage);
            }
        }
        
        if (errorList.length > 0) {
            throw new Error(errorList.join("<br>"));
        }

    }


    async function sendFormData(token) {
        let formData = new FormData(document.querySelector("#simpleform"));
        formData.set("captchaToken", token);
    
        const response = await fetch('./', {
            method: 'POST',
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        });
    
        if (!response.ok) {
            // This means the HTTP response status is not in the 200-299 range.
            // We throw an error to catch it in the main error handling mechanism.
            throw new Error("Server returned an error. Status: " + response.status);
        }
    
        const textData = await response.text();
    
        try {
            let data = JSON.parse(textData);
            return data; // This will be the `response` in handleForm's await sendFormData(token)
        } catch (e) {
            // If there's an error parsing the JSON, we throw an error with a custom message
            const errorMessage = errorMessages["json_parse_error"][pageLanguage];
            throw new Error(errorMessage);
        }
    }
        

    function getRecaptchaToken() {
        return new Promise((resolve, reject) => {
            const TIMEOUT = 10000; // Set timeout to 5 seconds
    
            // Set up a timer to reject the promise after 5 seconds
            const timer = setTimeout(() => {
                reject(new Error("ReCAPTCHA took too long to respond"));
            }, TIMEOUT);
    
            grecaptcha.ready(() => {
                grecaptcha.execute(ReCaptchaSiteKey, { action: 'submit' })
                    .then(token => {
                        clearTimeout(timer);  // Clear the timer if we got the token
                        if (token) {
                            resolve(token);
                        } else {
                            reject(new Error("Received an empty ReCAPTCHA token"));
                        }
                    });
            });
        }); 
    }


    function validateEmail(email) {
        return email.includes('@') && email.includes('.');
    }

});

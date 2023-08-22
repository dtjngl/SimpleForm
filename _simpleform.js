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

        let response; // Declare response here

        try {

            console.log("About to validate...");
            validateForm(); // If there's an error, it will throw and go to the catch block below
            
            console.log("About to get token...");
            let token = await getRecaptchaToken();
            console.log("Token received:", token);
            
            console.log("About to send form data...");
            let response = await sendFormData(token);
            console.log("Data sent successfully!");

            const infoalert = document.getElementById('infoalert');
            submitButton.disabled = false;

            if (response.errors && response.errors.length > 0) {
                // console.error(response.errors);
                let serverError = '';
                response.errors.forEach(err => {
                    let currentError = errorMessages["server_error"][pageLanguage].replace("{error}", err);
                    serverError += currentError + '<br>';
                    infoalert.innerHTML += currentError + '<br>';
                });
            
                throw new Error(serverError);

            }

            const successMessage = errorMessages["form_success"][pageLanguage];
            infoalert.innerHTML = successMessage;
            
            if (response && response.successURL) {
                setTimeout(() => {
                    window.location.href = window.location.origin + response.successURL;
                }, 3000);
            }

        } catch (error) {
            // Handle any errors here, either from validateForm, recaptcha or sendFormData
            infoalert.innerHTML = error.message;
            console.log(error)
            submitButton.disabled = false;
            document.getElementById('loadingOverlay').style.display = 'none';

            if (isJSON(error.message)) {
                let parsedError = JSON.parse(error.message);
                if (parsedError.errorURL) {
                    console.log('errorURL: ' + parsedError.errorURL); // Moved this inside if block.
                    setTimeout(() => {
                        window.location.href = window.location.origin + response.errorURL;
                    }, 3000);
                }
            }
    
        }

    }
    
    function isJSON(str) {
        try {
            JSON.parse(str);
            return true;
        } catch (e) {
            return false;
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
        const formData = new FormData(document.querySelector("#simpleform"));
        formData.set("captchaToken", token);
    
        const response = await fetch('./', {
            method: 'POST',
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        });
    
        console.log('Response Status:', response.status, response.statusText);
        console.log('Response Headers:', response.headers.get('Content-Type'));
    
        const textData = await response.text();
        console.log('Raw Text Response:', textData);
    
        if (response.status === 500) {
            console.log('500-error: ' + response);
            throw new Error("The server encountered an issue. Please try again later.");
        }
    
        let data;
        try {
            data = JSON.parse(textData);
        } catch (e) {
            const errorMessage = errorMessages["json_parse_error"][pageLanguage];
            throw new Error(errorMessage);
        }
    
        return data; // Return the parsed JSON data
    }

    
    // async function sendFormData(token) {
    //     const formData = new FormData(document.querySelector("#simpleform"));
    //     formData.set("captchaToken", token); // appending the received token
    
    //     const response = await fetch('./', {
    //         method: 'POST',
    //         body: formData,
    //         headers: {
    //             "X-Requested-With": "XMLHttpRequest"
    //         }
    //     });
    
    //     console.log('Response Status:', response.status, response.statusText);
    //     console.log('Response Headers:', response.headers.get('Content-Type'));
    
    //     const textData = await response.text();
    //     console.log('Raw Text Response:', textData);
    
    //     if (response.status === 500) {
    //         throw new Error("The server encountered an issue. Please try again later.");
    //     }

    //     let data;
    //     try {
    //         data = JSON.parse(textData);
    //     } catch (e) {
    //         const errorMessage = errorMessages["json_parse_error"][pageLanguage];
    //         throw new Error(errorMessage);
    //     }
    
    //     const infoalert = document.getElementById('infoalert');

    //     if (data.errors && data.errors.length > 0) {
    //         console.error(data.errors);
    //         data.errors.forEach(err => {
    //             const serverError = errorMessages["server_error"][pageLanguage].replace("{error}", err);
    //             infoalert.innerHTML += serverError + '<br>';
    //         });
    //         infoalert.style.display = 'inline-block'; // Ensure error display
    //         if (data.errorURL) {
    //             console.log("Error URL:", data.errorURL);
    //             setTimeout(() => {
    //                 window.location.href = window.location.origin + data.errorURL; // Redirect to error page after showing the error for a short duration
    //             }, 3000); // Change this value to adjust the wait time before redirection
    //         }
    //     } else {
    //         const successMessage = errorMessages["form_success"][pageLanguage];
    //         infoalert.innerHTML = successMessage;
    //         infoalert.style.display = 'inline-block'; // Ensure success message display
    //         console.log(data);
    //         if (data.successURL) {
    //             console.log("Success URL:", data.successURL);
    //             setTimeout(() => {
    //                 window.location.href = window.location.origin + data.successURL; // Redirect to success page after showing the message for a short duration
    //             }, 3000); // Change this value to adjust the wait time before redirection
    //         }
    //     }
    
    //     document.getElementById('loadingOverlay').style.display = 'none';
    //     return data;
    // }


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

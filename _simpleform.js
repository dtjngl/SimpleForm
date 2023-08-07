document.addEventListener('DOMContentLoaded', (event) => {
    
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
        const infoalert = document.getElementById('infoalert');
        const fields = document.querySelectorAll("#simpleform input, #simpleform textarea");
        const privacyCheckbox = document.getElementById('privacyCheckbox');

        let valid = true;
        infoalert.innerHTML = ''; // Clear the alert box at the start of each validation attempt.

        fields.forEach((field) => {
            if (field.hasAttribute('required') && !field.value) {
                valid = false;
                infoalert.innerHTML += 'Das Feld ' + field.title + ' muss ausgefüllt werden!<br>'; // Add the error message for this field to the alert box.
            }
            if (field.type === 'email' && !validateEmail(field.value)) {
                valid = false;
                infoalert.innerHTML += 'Die Email-Adresse ist nicht gültig!<br>'; // Add the error message for this field to the alert box.
            }
        });

        if (!privacyCheckbox.checked) {
            valid = false;
            infoalert.innerHTML += 'Sie müssen die Datenschutzerklärung akzeptieren!<br>'; // Add an error message for the privacy policy acceptance.
        }

        if(valid) {
            sendFormData();
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
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    throw new Error('Server did not respond with JSON.');
                }
            })
            // .then(data => {
            //     const infoalert = document.getElementById('infoalert');
            //     if(data.errors) {
            //         // Handle errors here
            //         console.error(data.errors);
            //         data.errors.forEach(err => {
            //             infoalert.innerHTML += err + '<br>'; // Add the error messages returned by the server to the alert box.
            //         });
            //     } else {
            //         // Handle success here
            //         infoalert.innerHTML = 'Form successfully submitted!'; // Add a success message to the alert box.
            //         infoalert.style.display = 'inline-block';
            //         console.log(data);
            //     }
            // })
            .then(data => {
                if(data.redirectURL) {
                    console.log(data.redirectURL);
                    console.log(data.errors);
                    window.location.href = window.location.origin + data.redirectURL;
                } else {
                    // Handle the response as before
                    const infoalert = document.getElementById('infoalert');
                    if(data.errors) {
                        // Handle errors here
                        console.error(data.errors);
                        data.errors.forEach(err => {
                            infoalert.innerHTML += err + '<br>'; // Add the error messages returned by the server to the alert box.
                        });
                    } else {
                        // Handle success here
                        infoalert.innerHTML = 'Form successfully submitted!'; // Add a success message to the alert box.
                        infoalert.style.display = 'inline-block';
                        console.log(data);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            })
        );
    }
    
    function validateEmail(email) {
        const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,2,3,4,5,6,7,8,9}\.]))|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,})$/;
        return re.test(email);
    }

});

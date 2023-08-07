window.addEventListener('load', loadAsWell, true);

function loadAsWell () {

    // var salutation = document.getElementById('salutation');
    // var givenname = document.getElementById('givenname');
    // var familyname = document.getElementById('familyname');
    // var subject = document.getElementById('subject');
    // var message = document.getElementById('message');
    var emailaddress = document.getElementById('emailaddress');
    var privacyCheckbox = document.getElementById('privacyCheckbox');
    var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;

    var infoalert = document.getElementById('infoalert');
    var required = document.getElementsByClassName('required');
    // var contactForm = document.getElementById('contact');
    // var check = 0;

    const sendform = document.getElementById('sendform');
    sendform.addEventListener('click', checkInput);

    showHide();

    function showHide() {
        if(infoalert.innerHTML != '') {
            infoalert.style.display = ('inline-block');
        } else {
            infoalert.style.display = ('none');
        }
    }

    const errormessages = [
        'Vorname fehlt.<br>',
        'Nachname fehlt.<br>',
        'E-Mail-Adresse fehlt.<br>',
        'Betreff fehlt.<br>',
        'Nachricht fehlt.<br>',
    ];

    function checkInput() {

        console.log('checking input…');

        var check = 0;

        infoalert.innerHTML = '';
        infoalert.classList.remove("uk-alert-success");
        infoalert.classList.add("uk-alert-warning");
        showHide();

        for(i=0;i<required.length;i++) {     
            // if (required[i].value == '') {
            if (!required[i].value.trim().length) {
                infoalert.innerHTML = (infoalert.innerHTML+errormessages[i]);  
                required[i].classList.add('uk-alert-warning');
                window.scrollTo({top: 0, behavior: 'smooth'});        
                required[i].addEventListener('change', function() {
                    this.classList.remove('uk-alert-warning');
                })
                check++;
            } else {
                required[i].classList.remove('uk-alert-warning');
            }
        }

        if (emailaddress.value != '' && reg.test(emailaddress.value) == false) {
            infoalert.innerHTML = (infoalert.innerHTML+'E-Mail-Adressformat ist falsch.<br>');
            emailaddress.classList.add('uk-alert-warning');
            window.scrollTo({top: 0, behavior: 'smooth'});
            check++;    
        }        

        if (privacyCheckbox.checked == false) {
            infoalert.innerHTML = (infoalert.innerHTML+"Bitte akzeptieren Sie unsere Datenschutzerklärung und AGB.<br>");  
            privacyCheckbox.classList.add('uk-alert-warning');
            window.scrollTo({top: 0, behavior: 'smooth'});
            check++;
        }

        showHide();

        if (check == 0) {
            sendform.disabled = true;
            createFormData();
        }

    }


    function createFormData() {

        console.log('creating formData…');

        var formData = new FormData(); 
        var formFields = document.getElementsByClassName('formfield');

        for(let i=0; i < formFields.length; i++) {
            formData.set(formFields[i].name, formFields[i].value);
        }

        const grecaptcha = document.getElementById('grecaptcha');
        const ReCaptchaSiteKey = grecaptcha.dataset.sitekey;

        grecaptcha.ready(() =>
            grecaptcha.execute(ReCaptchaSiteKey, { action: 'submit' })
            .then(token => {formData.set("captchaToken", token);})
            .then(() => sendFormData(formData))
        )

    }

    function sendFormData(formData) {

        console.log('sending formData…');

        var XHR = new XMLHttpRequest();
        XHR.onreadystatechange = function () {

            if (XHR.readyState !== 4) return;
            if (XHR.status >= 200 && XHR.status < 300) {

                try {

                    console.log("redirecting…");
                    console.log(XHR.response);
                    // response_obj = JSON.parse(XHR.response);
                    // window.location.href = response_obj.redirectURL;


                } catch(err) {

                    console.log("error");

                    // response_obj = JSON.parse(XHR.response);
                    // console.log(response_obj.errors);
                    // window.location.href = response_obj.redirectURL;

                    console.log(XHR.response);

                }

            }

        };

        XHR.open('POST', '', true);
        XHR.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // :D
        // XHR.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        XHR.send(formData);

    }

}
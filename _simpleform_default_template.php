<?php namespace ProcessWire; ?>

    <form id="simpleform" method="post" action="/submit" enctype="multipart/form-data">

        <h1 class="centered uk-margin-medium uk-margin-bottom-large"><?=wire('page')->title?></h1>

        <div class="uk-grid-small uk-margin-auto uk-width-1-1@s uk-width-1-2@m">
                
                <div id="infoalert" class="infoalert uk-input uk-width-1-1 uk-margin-auto uk-flex-center uk-alert-warning"></div>
                    
                <h3>Anrede, Vorname* und Nachname*</h3>

                <input 
                    class="formfield uk-input uk-width-1-1 uk-flex-center" 
                    type="text" 
                    id="salutation" 
                    title="Anrede" 
                    name="salutation" 
                    placeholder="Anrede"
                    >
                    
                <input 
                    class="formfield required uk-input uk-width-1-1 uk-flex-center" 
                    type="text" 
                    id="givenname" 
                    title="Vorname" 
                    name="givenname" 
                    placeholder="Vorname"
                    required
                    >
                    
                <input 
                    class="formfield required uk-input uk-width-1-1 uk-flex-center" 
                    type="text" 
                    id="familyname" 
                    title="Nachname" 
                    name="familyname" 
                    placeholder="Nachname"
                    required
                    >
                    
                <h3>E-Mail-Adresse*</h3>
                    
                <input 
                    class="formfield required uk-input uk-width-1-1 uk-flex-center" 
                    type="email" 
                    title="E-Mail-Adresse" 
                    name="emailaddress" 
                    id="emailaddress" 
                    placeholder="E-Mail-Adresse"
                    onDrag="return false" 
                    onDrop="return false"
                    required
                    >
                                                    
                <h3>Betreff*</h3>
                    
                <input 
                    class="formfield required uk-input uk-width-1-1 uk-flex-center" 
                    type="text" 
                    id="subject" 
                    title="Betreff" 
                    name="subject" 
                    placeholder="Betreff"
                    required
                    >
                                        
                <h3>Nachricht*</h3>
                        
                <textarea 
                    class="formfield required uk-textarea uk-width-1-1 uk-flex-center" 
                    title="Nachricht" 
                    name="message" 
                    id="message" 
                    rows="12" 
                    placeholder="Nachricht" required></textarea>

                <div class="uk-margin uk-grid-small uk-child-width-auto uk-grid">
                    <label><input id="privacyCheckbox" class="uk-checkbox" type="checkbox">&nbsp;&nbsp;ich habe die <a href="<?=wire('pages')->get('datenschutz')->url?>" target="_blank">Datenschutzerklärung</a> zur Kenntnis genommen und akzeptiere diese.</label>
                </div>

                <input 
                    class="uk-input uk-width-1-1 uk-margin-small uk-flex-center" 
                    type="hidden" 
                    id="token" 
                    title="token" 
                    name="token">

                <div class="uk-margin">
                    <div class="uk-inline uk-width-1-1">
                        <span class="uk-form-icon" uk-icon="icon: upload"></span>
                        <input class="uk-input" type="file" id="attachment" name="attachment">
                    </div>
                </div>

                <h4><strong>* Pflichtfelder</strong></h4>

                <div id="grecaptcha" class="g-recaptcha"
                data-sitekey="<?=$this->google_recaptcha_site_key?>"
                data-size="invisible">
                </div>

                <input 
                    type="submit"
                    id="sendform"
                    title="submit" 
                    class="g-recaptcha centered uk-button uk-button-primary uk-width-1-2@m uk-width-1-1@s uk-form-width-medium uk-align-right uk-margin-medium-top noselect" 
                    name="sendform" 
                    value="Absenden"
                />
                
        </div>

    </form>

    <!-- <script type="text/javascript" src="<?=urls()->templates?>scripts/_contact.js?<?=time()?>"></script> -->

    <script src="https://www.google.com/recaptcha/api.js?render=<?=$this->google_recaptcha_site_key?>"></script>
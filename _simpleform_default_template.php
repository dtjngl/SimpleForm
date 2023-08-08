<?php namespace ProcessWire; ?>

    <form id="simpleform" method="post" action="/submit" lang="<?=$this->user->language->name?>" enctype="multipart/form-data">

        <h1 class="centered uk-margin-medium uk-margin-bottom-large"><?=wire('page')->title?></h1>

        <div class="uk-grid-small uk-margin-auto uk-width-1-1@s uk-width-1-2@m">
                
                <div id="infoalert" class="infoalert uk-input uk-width-1-1 uk-margin-auto uk-flex-center uk-alert-warning"></div>
                    
                <h3><?=_x('Anrede, Vorname und Nachname', 'simpleform');?>*</h3>

                <input 
                    class="formfield uk-input uk-width-1-1 uk-flex-center" 
                    type="text" 
                    id="salutation" 
                    title="<?=_x('Anrede', 'simpleform');?>" 
                    name="salutation" 
                    placeholder="<?=_x('Anrede', 'simpleform');?>"
                    >
                    
                <input 
                    class="formfield required uk-input uk-width-1-1 uk-flex-center" 
                    type="text" 
                    id="givenname" 
                    title="<?=_x('Vorname', 'simpleform');?>" 
                    name="givenname" 
                    placeholder="<?=_x('Vorname', 'simpleform');?>"
                    data-error-key-required="required_givenname"
                    required
                    >
                    
                <input 
                    class="formfield required uk-input uk-width-1-1 uk-flex-center" 
                    type="text" 
                    id="familyname" 
                    title="<?=_x('Nachname', 'simpleform');?>" 
                    name="familyname" 
                    placeholder="<?=_x('Nachname', 'simpleform');?>"
                    data-error-key-required="required_familyname"
                    required
                    >
                    
                <h3><?=_x('E-Mail-Adresse', 'simpleform');?>*</h3>

                <input 
                    class="formfield required uk-input uk-width-1-1 uk-flex-center" 
                    type="email" 
                    title="<?=_x('E-Mail-Adresse', 'simpleform');?>" 
                    name="emailaddress" 
                    id="emailaddress" 
                    placeholder="<?=_x('E-Mail-Adresse', 'simpleform');?>"
                    onDrag="return false" 
                    onDrop="return false"
                    data-error-key-required="required_emailaddress"
                    data-error-key-wrong="required_emailaddress"
                    required
                    >
                                                    
                <h3><?=_x('Betreff', 'simpleform');?>*</h3>

                <input 
                    class="formfield required uk-input uk-width-1-1 uk-flex-center" 
                    type="text" 
                    id="subject" 
                    title="<?=_x('Betreff', 'simpleform');?>" 
                    name="subject" 
                    placeholder="<?=_x('Betreff', 'simpleform');?>"
                    data-error-key-required="required_subject"
                    required
                    >
                                        
                <h3><?=_x('Nachricht', 'simpleform');?>*</h3>

                <textarea 
                    class="formfield required uk-textarea uk-width-1-1 uk-flex-center" 
                    title="<?=_x('Nachricht', 'simpleform');?>" 
                    name="message" 
                    id="message" 
                    rows="12" 
                    placeholder="<?=_x('Nachricht', 'simpleform');?>" 
                    data-error-key-required="required_message"
                    required></textarea>

                <div class="uk-margin uk-grid-small uk-child-width-auto uk-grid">
                    <label>
                        <input id="privacyCheckbox" class="uk-checkbox" type="checkbox" data-error-key-required="required_privacyCheckbox" required/>
                        &nbsp;&nbsp;<?php echo $this->checkAndGetLanguageValue('privacy_checkbox_text', '__');?>
                    </label>
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

                <h3>* <?=_x('Pflichtfelder', 'simpleform');?></h3>

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
                    value="<?=_x('absenden', 'simpleform');?>"
                />
                
        </div>

    </form>

    <!-- <script type="text/javascript" src="<?=urls()->templates?>scripts/_contact.js?<?=time()?>"></script> -->

    <script src="https://www.google.com/recaptcha/api.js?render=<?=$this->google_recaptcha_site_key?>"></script>
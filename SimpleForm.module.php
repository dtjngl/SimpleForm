<?php namespace ProcessWire; 

    class SimpleForm extends WireData implements Module, ConfigurableModule {

        public static function getModuleInfo() {
            return array(
                'title' => 'SimpleForm',
                'version' => '1.0.0',
                'summary' => 'A module to handle a contact form with Google reCAPTCHA in ProcessWire.',
                'singular' => true,
                'autoload' => true,
                'author' => 'FRE:D',
                'installs' => [
                    'SimpleFormSettings',
                    'WireMailSmtp',
                    'FieldtypeCKEditor'
                ],
                'icon' => 'envelope-o'
            );
        }
    
        public function __construct() {
            $simpleFormSettings = wire('modules')->getConfig($this);
            foreach ($simpleFormSettings as $key => $value) {
                $this->$key = $value;
            }
        }
    
        protected function renderSimpleForm() {
            if($this->simpleform_template != '') {
                include($this->simpleform_template);
            } else {
                require_once('_simpleform_default_template.php');
            }
        }
    
        protected function checkAndGetLanguageValue(string $key, string $x='') {
            $fieldNameString = $this->getLanguageString($key, $x);
            return $this->$fieldNameString;
        }

        protected function getLanguageString(string $key, string $x) {
            $language = $this->user->language;
            if ($language->name !== 'default') {
                $string = $key.$x.$language->id;
                return $string;
            } 
            return $key;
        }
    
        public function ___install() {
            wire('modules')->saveModuleConfigData($this, self::simpleFormSettingsDefaults()); 

            $fields = $this->fields;

            // Check if the 'body' field exists, if not create it
            $bodyField = $fields->get('body');
            if(!$bodyField) {
                $bodyField = new Field();
                $bodyField->type = $this->modules->get("FieldtypeCKEditor");
                $bodyField->name = 'body';
                $bodyField->label = 'Body';
                $bodyField->save();
            }


            if(!$this->templates->get('simpleform')) :
                
                // Create a new fieldgroup
                $fg = new Fieldgroup();
                $fg->name = 'simpleform';
                $fg->add($fields->get('title'));
                $fg->add($fields->get('body'));
                $fg->save();

                // Create a new 'simpleform' template
                $contactTemplate = new Template();
                $contactTemplate->name = 'simpleform';
                $contactTemplate->fieldgroup = $fg;
                $contactTemplate->save();

            endif;

            if(!$this->templates->get('simpleform-submitted')) :

                // Create a new fieldgroup
                $fg = new Fieldgroup();
                $fg->name = 'simpleform-submitted';
                $fg->add($fields->get('title'));
                $fg->add($fields->get('body'));
                $fg->save();
                
                // Create a new 'simpleform-submitted' template with 'body' field
                $submittedTemplate = new Template();
                $submittedTemplate->name = 'simpleform-submitted';
                $submittedTemplate->fieldgroup = $fg;
                $submittedTemplate->save();

            endif;


            // Create the parent page ("Contact")
            $contactPage = new Page();
            $contactPage->template = $contactTemplate; // Use new 'simpleform' template
            $contactPage->parent = $this->pages->get('/'); // Set parent page
            $contactPage->name = 'contact';
            $contactPage->title = 'Contact';
            $contactPage->save();

            // Create "success" and "error" pages under "/contact/"
            $successPage = new Page();
            $successPage->template = $this->templates->get('simpleform-submitted');
            $successPage->parent = $this->pages->get('/contact/');
            $successPage->title = 'Success';
            $successPage->name = 'success'; // This will be the URL slug (i.e., /contact/success/)
            $successPage->body = 'Your form has been successfully submitted!'; // Default success message
            $successPage->save();

            $errorPage = new Page();
            $errorPage->template = $this->templates->get('simpleform-submitted');
            $errorPage->parent = $this->pages->get('/contact/');
            $errorPage->title = 'Error';
            $errorPage->name = 'error'; // This will be the URL slug (i.e., /contact/error/)
            $errorPage->body = 'An error occurred while submitting your form. Please try again.'; // Default error message
            $errorPage->save();

            // Make all pages active for all languages
            $languages = $this->languages;
            $pagesToActivate = array($contactPage, $successPage, $errorPage);
            foreach($pagesToActivate as $page) {
                foreach($languages as $language) {
                    $page->set("status{$language->id}", 1); // Sets the page as active for this language
                }
                $page->save(); // Save the status change
            }

            // Copying a default PHP file for your template
            $defaultTemplateFilePath = __DIR__ . '/default-simpleform.php';
            $targetTemplateFilePath = $this->config->paths->templates . 'simpleform.php';
            
            if(!copy($defaultTemplateFilePath, $targetTemplateFilePath)) {
                throw new WireException("Unable to copy the default template file: $defaultTemplateFilePath");
            }

            // Copying a default PHP file for your template
            $defaultTemplateFilePath = __DIR__ . '/default-simpleform-submitted.php';
            $targetTemplateFilePath = $this->config->paths->templates . 'simpleform-submitted.php';
            
            if(!copy($defaultTemplateFilePath, $targetTemplateFilePath)) {
                throw new WireException("Unable to copy the default template file: $defaultTemplateFilePath");
            }

        }

        public function ___uninstall() {

            // Trash or delete the pages
        
            $simpleFormPages = array(
                '/contact',
                '/contact/success',
                '/contact/error',
            );
            
            foreach ($simpleFormPages as $sfpPath) {
                $sfp = $this->pages->get($sfpPath);
                if($sfp->id) { // check if the page really exists
                    $sfp->delete(true); // true forces the deletion even if trash is active
                }
            }
        

            // Delete the 'simpleform' and 'simpleform-submitted' templates and fieldgroups
            $templateNames = array('simpleform', 'simpleform-submitted');
            foreach($templateNames as $templateName) {
                $template = $this->templates->get($templateName);
                if($template) {
                    // All pages using the template should be deleted before
                    $this->templates->delete($template);
                }
                
                // Delete fieldgroup
                $fg = $this->fieldgroups->get($templateName);
                if($fg) {
                    $this->fieldgroups->delete($fg);
                }
            }


            // Deleting the template file

            $templateFilePaths = array(
                $this->config->paths->templates.'simpleform.php', 
                $this->config->paths->templates.'simpleform-submitted.php'
            );

            foreach ($templateFilePaths as $tfp) {
                if(file_exists($tfp) && !unlink($tfp)) {
                    throw new WireException("Unable to delete the template file: $tfp");
                }
            }
                
        }
        

        public function init() {
            $this->checkOutPage = $this->pages->get('template=simpleForm_checkout');   
        }
    

        public function addScripts() {
            $additionalScripts = '<!-- :D this is the addScripts() hook :D -->';
            $additionalScripts .= '<script type="text/javascript" src="'.wire('urls')->httpSiteModules.'SimpleForm/_simpleform.js?v='.time().'"></script>';
            $additionalScripts .= '<!-- :D this is the addScripts() hook :D -->';
            return $additionalScripts;
        }
    
    
        public function simpleFormSettingsDefaults() {
            return array(
                'sender_name' => 'DataJungle Kontaktform',
                'sender_email' => 'hi@datajungle.xyz',
                'receiver_name' => 'DataJungle Office',
                'receiver_email' => 'hi@datajungle.xyz',
                'bcc_debug_email' => 'hi@datajungle.xyz',
                'email_imprint' => 'DataJungle Email Imprint',
                'privacy_checkbox_text' => 'I have read the <a href="privacy-policy">privacy policy</a> and accept them.',
                'allowed_attachment_format_extensions' => 'pdf doc docx jpg jpeg',
                'success_url' => '/contact/success',
                'error_url' => '/contact/error',
                'google_recaptcha_site_key' => '',
                'google_recaptcha_secret_key' => ''
            );
        }
    
    
        public function handleAJAX($input) {

            $response = [];
            $response['errors'] = [];
            $response['status'] = '';
            $response['message'] = '';
            $response['redirectURL'] = '';

            try {
                $captchaResponse = $this->getCaptcha($input->post->captchaToken);
                if (isset($captchaResponse) && $captchaResponse->success == false) {
                    throw new WireException('Captcha abgelaufen');
                }
                $this->sendOrderEmails($input, $response);
        
                // If no exceptions were thrown, the operation was successful
                $response['status'] = 'success';
                $response['message'] = 'Email sent successfully.';
                $response['redirectURL'] = $this->success_url;
        
            } catch (\Throwable $err) {
                $response['status'] = 'error';
                array_push($response['errors'], 'Email konnte nicht versendet werden: ' . $err->getMessage());
                $response['message'] = __('Something went wrong, we are taking care of it.');
                $this->sendErrorEmail($err);
                $response['redirectURL'] = $this->error_url;
            }
        
            header('Content-Type: application/json');
            echo json_encode($response);
                                    
        }
            
        // public function handleStaticContent($input) {
        //     if($this->issetOrder()==false){$this->resetOrder();}
        //     if($this->issetPayPalSession()==false){$this->resetPayPalSession();}
        //     if(isset($input->get->PayerID)){$this->setPayPalPayerId($input->get->PayerID);}
        //     if(isset($input->get->token)){$this->setPayPalToken($input->get->token);}
        // }
            



        
        protected function sendOrderEmails($input, &$response) {

            $uploadPath = $this->config->paths->assets . 'files/'; // Define the file upload path
            $maxFileSize = 1024 * 1024; // Set a maximum file size, e.g., 1MB
            $allowedFileTypes = explode(" ", $this->allowed_attachment_format_extensions); // Define allowed file types
            $allowedFileTypes = array_map('trim', $allowedFileTypes);
            bd($allowedFileTypes);
            $filename = '';

            // try {
            //     $filename = $this->handleFileUpload($input, $response, $uploadPath, $maxFileSize, $allowedFileTypes);
            // } catch (WireException $err) {
            //     array_push($response['errors'], 'Email konnte nicht versendet werden: ' . $err->getMessage());
            //     throw $err;
            // }

            // If a file was uploaded, handle it
            if (!empty($_FILES['attachment']['name'])) {
                try {
                    $filename = $this->handleFileUpload($input, $response, $uploadPath, $maxFileSize, $allowedFileTypes);
                } catch (WireException $err) {
                    array_push($response['errors'], _x('Email konnte nicht versendet werden:', 'SimpleForm') . ' ' . $err->getMessage());
                    throw $err;
                }
            }

            // Proceed with sending the email
            $wireemail_order = wireMail();
            $wireemail_order->to($this->receiver_email);
            $wireemail_order->toName($this->checkAndGetLanguageValue($this->receiver_name, '__'));
            $wireemail_order->from($this->sender_email);
            $wireemail_order->fromName($this->checkAndGetLanguageValue($this->sender_name, '__')); 
            
            if($this->bcc_debug_email!=''){
                $wireemail_order->bcc($this->bcc_debug_email);
            }
            
            $wireemail_order->subject($input->post->subject);
            $wireemail_order->bodyHTML($input->post->message);
            $wireemail_order->replyto($input->post->emailaddress);
            
            // The file validation and handling code comes first
            // $inputfile = $input->files('file'); // Retrieve the uploaded file
                            
            if ($filename) {
                $wireemail_order->attachment($uploadPath . $filename);
            }

            $numSent = $wireemail_order->send();
        
            $wireemail_order->logActivity($wireemail_order); // you may log success if you want
            $wireemail_order->logError($wireemail_order); // you may log errors, too. - Errors are also logged automaticaly
        
            $response['data'] = json_encode($wireemail_order);
            return $numSent > 0;
            
        }
        

        protected function handleFileUpload($input, &$response, $uploadPath, $maxFileSize, $allowedFileTypes) {
            
            if (empty($_FILES['attachment']['name'])) {
                throw new WireException('No file uploaded.');
            }
            if ($_FILES['attachment']['error'] != UPLOAD_ERR_OK) {
                throw new WireException('An error occurred during file upload. Error code: ' . $_FILES['attachment']['error']);
            }
            if ($_FILES['attachment']['size'] > $maxFileSize) {
                throw new WireException('File size exceeds the limit of ' . $maxFileSize . ' bytes.');
            }
            
            $fileInfo = pathinfo($_FILES['attachment']['name']);
            $fileExtension = strtolower($fileInfo['extension']);
            
            bd($fileExtension);

            if (!in_array($fileExtension, $allowedFileTypes)) {
                throw new WireException('Invalid file type. Allowed file types are: ' . implode(', ', $allowedFileTypes));
            }
            
            $u = new WireUpload('attachment');
            $u->setMaxFileSize($maxFileSize);
            $u->setOverwrite(true);
            $u->setDestinationPath($uploadPath);
            $u->setValidExtensions($allowedFileTypes);
            
            $filenames = $u->execute();
            
            if(!$filenames) {
                throw new WireException('File could not be saved: ' . implode(', ', $u->getErrors(true)));
            }
            $filename = $filenames[0];
                        
            return $filename;

        }

        protected function sendErrorEmail($err) {
            $e = wireMail(); 
            $e->to($this->bcc_debug_email);
            $e->toName($this->receiver_name);
            $e->from($this->bcc_debug_email);
            $e->fromName($this->sender_name); 
            $e->subject('fehler im simpleForm');
            $e->body($err);
            $e->bodyHTML('<pre>'.$err.'</pre>');
            $numSent = $e->send();      
        }
        
        
        // SETTER AND GETTER
    
        //   public function setPayPalAccessToken(string $PayPalAccessToken):void{
        //     $_SESSION['simpleForm']['PayPalSession']['PayPalAccessToken'] = $PayPalAccessToken;
        //   }
    
        //   public function getPayPalAccessToken():?string{
        //     return isset($_SESSION['simpleForm']['PayPalSession']['PayPalAccessToken'])?$_SESSION['simpleForm']['PayPalSession']['PayPalAccessToken']:null;
        //   }
    
        public function getSuccessURL() {
            return $this->pages->get('/')->httpUrl.$this->checkAndGetLanguageValue('success_url', '__');
        }
    
        public function getErrorURL() {
            return $this->pages->get('/')->httpUrl.$this->checkAndGetLanguageValue('error_url', '__');
        }

        protected function getCaptcha($token) {
            // $return_json = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.SECRET_KEY.'&response='.$token);
            $return_json = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$this->google_recaptcha_secret_key.'&response='.$token);
            $return = json_decode($return_json);
            $return_json = json_encode($return);
            session()->set('captcharesponse', $return_json);
            return $return;
        }

    }


?>
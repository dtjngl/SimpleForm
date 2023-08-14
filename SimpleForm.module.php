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

            $uploadPath = $this->config->paths->assets . 'SimpleFormUploads/';

            // Ensure the directory exists, and if not, create it
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

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

            $uploadPath = $this->config->paths->assets . 'SimpleFormUploads/';

            // Delete all files in the directory
            foreach (glob("{$uploadPath}*") as $file) {
                unlink($file);
            }

            // Remove the directory
            rmdir($uploadPath);

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
                'success_url' => '/contact/success',
                'error_url' => '/contact/error',
                'google_recaptcha_site_key' => '',
                'google_recaptcha_secret_key' => '',
                'simpleform_maxfileamount' => 10,
                'simpleform_max_total_filesize' => 10*1024*1024,
                'allowed_attachment_format_extensions' => 'pdf doc docx jpg jpeg'
            );
        }
    
    
        public function handleAJAX($input) {

            $response = [];
            $response['errors'] = [];
            $response['status'] = '';
            $response['message'] = '';
            $response['redirectURL'] = '';
            $adminEmailSuccess = false;

            // Captcha Check first
            $captchaResponse = $this->getCaptcha($input->post->captchaToken);

            if (isset($captchaResponse) && $captchaResponse->success == false) {
                $response['errors'][] = 'Captcha abgelaufen';
                
                // Immediately return if captcha is invalid
                $this->finalizeResponse($response, false);
                return;
            }

            $adminEmailSuccess = $this->sendAdminEmail($input, $response);
            // $userEmailErrors = $this->sendConfirmationEmail($input);
            
            if (!$adminEmailSuccess /* || !$userEmailSuccess */) {
                $response['errors'][] = "There was an issue sending emails.";
            }
                    
            // Determine success or error based on whether there were any errors accumulated
            $success = empty($response['errors']);
            $this->finalizeResponse($response, $success);

        }

        
        private function finalizeResponse(&$response, $success) {
            if($success) {
                $response['status'] = 'success';
                $response['message'] = 'Email sent successfully.';
                $response['redirectURL'] = $this->success_url;
            } else {
                $response['status'] = 'error';
                $response['message'] = __('Something went wrong, we are taking care of it.');
                $this->sendErrorEmail(implode(", ", $response['errors']));
                $response['redirectURL'] = $this->error_url;
            }
        
            header('Content-Type: application/json');

            echo json_encode($response);

        }
            
        
        protected function sendAdminEmail($input, &$response) {

            $uploadPath = $this->config->paths->assets . 'SimpleFormUploads/'; // Define the file upload path
            $maxFileSize = $this->simpleform_max_total_filesize; // Set a maximum file size, e.g., 1MB
            $allowedFileTypes = explode(" ", $this->allowed_attachment_format_extensions); // Define allowed file types
            $allowedFileTypes = array_map('trim', $allowedFileTypes);
            $savedFiles = []; // An array to store saved file names

            try {

                if (isset($input->files->attachment)) {
                    $this->validateUploadedFiles($input->files->attachment, $allowedFileTypes, $maxFileSize);
        
                    foreach ($input->files->attachment['tmp_name'] as $key => $tmpFilePath) {
                        $filename = basename($input->files->attachment['name'][$key]);
                        $destinationPath = $uploadPath . $filename;
                        if (move_uploaded_file($tmpFilePath, $destinationPath)) {
                            $savedFiles[] = $destinationPath;
                        } else {
                            throw new Exception("Failed to save uploaded file: $filename");
                        }
                    }
                }
        
                // Proceed with sending the email
                $wireemail = wireMail();
                $wireemail->to($this->receiver_email);
                $wireemail->toName($this->receiver_name);
                $wireemail->from($this->sender_email);
                $wireemail->fromName($this->sender_name); 
                
                if($this->bcc_debug_email!=''){
                    $wireemail->bcc($this->bcc_debug_email);
                }
                
                $wireemail->subject($input->post->subject);
                $wireemail->bodyHTML($input->post->message);
                $wireemail->replyto($input->post->emailaddress);
                                            
                // if (!empty($filename)) {
                //     foreach($filename as $file) {
                //         $wireemail->attachment($uploadPath . $file);
                //     }
                // }

                // if (isset($input->files->attachment)) {
                //     foreach($input->files->attachment['tmp_name'] as $tmpFilePath) {
                //         $wireemail->attachment($tmpFilePath);
                //     }
                // }

                echo '$_FILES';
                var_dump($_FILES);

                echo '$input->file';
                var_dump(isset($input->files));

                echo '$input->files->attachment';
                var_dump(isset($input->files->attachment));

                if (isset($input->files->attachment)) {
                    // foreach($input->files->attachment['tmp_name'] as $tmpFilePath) {
                    //     if (!file_exists($tmpFilePath)) {
                    //         throw new Exception("File does not exist: $tmpFilePath");
                    //     }
                    //     $wireemail->attachment($tmpFilePath);
                    // }
                    echo 'HI';
                    var_dump($input->files->attachment);
                } else {
                    echo "No attachment found!";
                }

                // Attach saved files to the email
                foreach ($savedFiles as $filePath) {
                    $wireemail->attachment($filePath);
                }

                $numSent = $wireemail->send();

                // Optionally, delete the saved files
                foreach ($savedFiles as $filePath) {
                    unlink($filePath);
                }

                if ($numSent == 0) {
                    throw new Exception("Failed to send email to admin.");
                }
        
                $wireemail->logActivity($wireemail); // you may log success if you want
                $wireemail->logError($wireemail); // you may log errors, too. - Errors are also logged automaticaly
            
                $response['data'] = json_encode($wireemail);
                
                return $numSent > 0;

            } catch (Exception $e) {
                $response['errors'][] = _x('Email konnte nicht versendet werden:', 'SimpleForm') . ' ' . $e->getMessage();
                return false; // Return failure status
            }
                
        }
        
        
        protected function validateUploadedFiles($fileData, $allowedFileTypes, $maxFileSize) {
            // Handling individual file errors
            foreach ($fileData['error'] as $error) {
                if ($error != UPLOAD_ERR_OK) {
                    $errorMessage = $this->getUploadErrorMessage($error);
                    throw new WireException($errorMessage);
                }
            }
        
            if (count($fileData['name']) > $this->simpleform_maxfileamount) {
                throw new WireException('Number of files exceeds the limit of ' . $this->simpleform_maxfileamount . ' files.');
            }
        
            // Checking total file sizes
            $totalSize = array_sum($fileData['size']);
            
            if ($totalSize > $maxFileSize) {
                throw new WireException('Total file size exceeds the limit of ' . $maxFileSize . ' bytes.');
            }
            
            // Checking file extensions
            foreach ($fileData['name'] as $filename) {
                $fileInfo = pathinfo($filename);
                $fileExtension = strtolower($fileInfo['extension']);
            
                if (!in_array($fileExtension, $allowedFileTypes)) {
                    throw new WireException('Invalid file type. Allowed file types are: ' . implode(', ', $allowedFileTypes));
                }
            }
        }

        
        protected function handleFileUpload($input, &$response, $uploadPath, $maxFileSize, $allowedFileTypes) {
    
            $filenames = [];
            
            // Handling individual file errors
            foreach ($input->files->attachment['error'] as $error) {
                if ($error != UPLOAD_ERR_OK) {
                    $errorMessage = $this->getUploadErrorMessage($error);
                    throw new WireException($errorMessage);
                }
            }

            if (count($input->files->attachment['name']) > $this->simpleform_maxfileamount) {
                throw new WireException('Number of files exceeds the limit of ' . $this->simpleform_maxfileamount . ' files.');
            }
            
            // Checking file sizes
            $totalSize = 0;
            foreach ($input->files->attachment['size'] as $size) {
                $totalSize += $size;
            }
            
            if ($totalSize > $maxFileSize) {
                throw new WireException('Total file size exceeds the limit of ' . $maxFileSize . ' bytes.');
            }
                        
            // Checking file extensions
            foreach ($input->files->attachment['name'] as $filename) {
                $fileInfo = pathinfo($filename);
                $fileExtension = strtolower($fileInfo['extension']);
            
                if (!in_array($fileExtension, $allowedFileTypes)) {
                    throw new WireException('Invalid file type. Allowed file types are: ' . implode(', ', $allowedFileTypes));
                }
            
                $filenames[] = $filename;  // Add each filename to the array
            }
                        
            $u = new WireUpload('attachment');
            $u->setMaxFileSize($maxFileSize);
            $u->setOverwrite(true);
            $u->setDestinationPath($uploadPath);
            $u->setValidExtensions($allowedFileTypes);
            $u->setMaxFiles($this->simpleform_maxfileamount);  // for example, allowing up to 10 files
            
            $filenames = $u->execute();
            
            if(!$filenames) {
                throw new WireException('Files could not be saved: ' . implode(', ', $u->getErrors(true)));
            }
                        
            return $filenames;  // This now returns an array of filenames
        
        }

        
        // A new helper function to provide a human-readable file upload error message
        protected function getUploadErrorMessage($code) {
            switch ($code) {
                case UPLOAD_ERR_INI_SIZE:
                    return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
                case UPLOAD_ERR_FORM_SIZE:
                    return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
                case UPLOAD_ERR_PARTIAL:
                    return 'The uploaded file was only partially uploaded.';
                case UPLOAD_ERR_NO_FILE:
                    return 'No file was uploaded.';
                case UPLOAD_ERR_NO_TMP_DIR:
                    return 'Missing a temporary folder.';
                case UPLOAD_ERR_CANT_WRITE:
                    return 'Failed to write file to disk.';
                case UPLOAD_ERR_EXTENSION:
                    return 'File upload stopped by extension.';
                default:
                    return 'Unknown upload error.';
            }
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
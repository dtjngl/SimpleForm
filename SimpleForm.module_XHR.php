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
                    'WireMailSmtp'
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
                include('_simpleform_default_template.php');
            }
        }
    
        public function ___install() {
            wire('modules')->saveModuleConfigData($this, self::simpleFormSettingsDefaults()); 
        }
        
        public function init() {
            $this->checkOutPage = wire('pages')->get('template=simpleForm_checkout');   
        }
    
        public function addScripts() {
            $additionalScripts = '<!-- :D this is the addScripts() hook :D -->';
            $additionalScripts .= '<script type="text/javascript" src="'.wire('urls')->httpSiteModules.'SimpleForm/_simpleform.js?v='.time().'"></script>';
            $additionalScripts .= '<!-- :D this is the addScripts() hook :D -->';
            return $additionalScripts;
        }
    
    
        public function simpleFormSettingsDefaults() {
            return array(
                'sender_name' => 'Atelier Pummer Kontaktform',
                'sender_email' => 'develop@atelier-pummer.at',
                'receiver_name' => 'Atelier Pummer Office',
                'receiver_email' => 'develop@atelier-pummer.at',
                'bcc_debug_email' => 'develop@atelier-pummer.at',
                'email_imprint' => 'Atelier Pummer Email Imprint',
                'privacy_checkbox_text' => 'I have read the <a href="terms-and-conditions">terms and conditions</a> and the <a href="privacy-policy">privacy policy</a> and accept them.',
                'success_url' => 'checkout/thanks',
                'error_url' => 'checkout/error',
                'google_recaptcha_site_key' => '',
                'google_recaptcha_secret_key' => ''
            );
        }
    
    
        public function handleAJAX($input) {

            $response = [];
            
            try {
                $captchaResponse = $this->getCaptcha($input->post->captchaToken);
                if (isset($captchaResponse) && $captchaResponse->success == false) {
                    throw new WireException('Captcha abgelaufen');
                }
                $this->sendOrderEmails($input, $response);
        
                // If no exceptions were thrown, the operation was successful
                $response['status'] = 'success';
                $response['message'] = 'Email sent successfully.';
        
            } catch (\Throwable $err) {
                $response['status'] = 'error';
                $response['errors'] = $err->getMessage();
                $response['message'] = __('Something went wrong, we are taking care of it.');
                $this->sendErrorEmail($err);
            }
        
            echo json_encode($response);
        }
            
        // public function handleStaticContent($input) {
        //     if($this->issetOrder()==false){$this->resetOrder();}
        //     if($this->issetPayPalSession()==false){$this->resetPayPalSession();}
        //     if(isset($input->get->PayerID)){$this->setPayPalPayerId($input->get->PayerID);}
        //     if(isset($input->get->token)){$this->setPayPalToken($input->get->token);}
        // }
            

        protected function sendOrderEmails($input, &$response) {
    
            try {
    
                $wireemail_order = wireMail(); 
        
                $wireemail_order->to($this->receiver_email);
                $wireemail_order->toName($this->receiver_name);
                $wireemail_order->from($this->sender_email);
                $wireemail_order->fromName($this->sender_name); 
        
                if($this->bcc_debug_email!=''){
                    $wireemail_order->bcc($this->bcc_debug_email);
                }
        
                $wireemail_order->subject($input->post->subject);
                $wireemail_order->bodyHTML($input->post->message);
                $wireemail_order->replyto($input->post->emailaddress);
        
                $numSent = $wireemail_order->send();
        
                $wireemail_order->logActivity($wireemail_order); // you may log success if you want
                $wireemail_order->logError($wireemail_order); // you may log errors, too. - Errors are also logged automaticaly
        
                bd($wireemail_order);

                $response['data'] = json_encode($wireemail_order);
        
            } catch (WireException $err) {
    
                // return $response['errors'] = $err->getMessage();
                return $response['errors'] .= 'Email konnte nicht versendet werden';
                throw $err;  // Re-throw the exception to be caught in handleAJAX()

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
            return wire('pages')->get('/')->httpUrl.$this->success_url;
        }
    
        public function getErrorURL() {
            return wire('pages')->get('/')->httpUrl.$this->error_url;
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
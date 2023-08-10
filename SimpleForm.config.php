<?php namespace ProcessWire;

$config = array(
      'sender_name' => array(
        'label' => 'Sender Name',
        'type' => 'text',
        'value' => '',
        'columnWidth' => 50,
        'useLanguages' => true
      ),
      'sender_email' => array(
        'label' => 'Sender Email',
        'type' => 'email',
        'value' => '',
        'columnWidth' => 50,
      ),
      'receiver_name' => array(
        'label' => 'Receiver Name',
        'type' => 'text',
        'value' => '',
        'columnWidth' => 50,
        'useLanguages' => true
      ),
      'receiver_email' => array(
        'label' => 'Receiver Email',
        'type' => 'email',
        'value' => '',
        'columnWidth' => 50,
      ),
      'bcc_debug_email' => array(
        'label' => 'BCC and Debug Email',
        'type' => 'email',
        'value' => '',
        'columnWidth' => 50,
      ),
      'simpleform_template' => array(
        'label' => 'SimpleForm Template',
        'type' => 'text',
        'value' => '',
        'description' => 'optional custom contact form template, if empty, default template /site/modules/SimpleForm/_simpleform_default_template.php will be used',
        'notes' => 'enter path relative to /site/templates/',
        'columnWidth' => 50,
      ),
      'email_imprint' => array(
        'label' => 'Email Imprint',
        'type' => 'CKEditor',
        'description' => 'enter the markup that appears at the end of each email send from the (when using a custom template, use like $this->email_imprint)',
        'value' => '',
        'useLanguages' => true
      ),
      'privacy_checkbox_text' => array(
        'label' => 'Text for the Privacy Checkbox',
        'type' => 'CKEditor',
        'description' => 'enter the markup that appears next to the mandatory privacy and terms and conditions checkbox',
        'notes' => 'I urge you to use "target=_blank" for any links',
        'value' => '',
        'useLanguages' => true
      ),
      'allowed_attachment_format_extensions' => array(
        'label' => 'Allowed attachments format extensions',
        'type' => 'text',
        'description' => 'enter the allowed format extensions for uploaded attachments separated by a white space',
        'value' => '',
      ),
      'success_url' => array(
        'label' => 'Success URL',
        'type' => 'text',
        'value' => '',
        'columnWidth' => 50,
        'description' => 'enter path to error URL including the language segment',
        'notes' => 'e.g. /en/contact/success',
        'useLanguages' => true,
      ),
      'error_url' => array(
        'label' => 'Error URL',
        'type' => 'text',
        'value' => '',
        'columnWidth' => 50,
        'description' => 'enter path to error URL including the language segment',
        'notes' => 'e.g. /en/contact/error',
        'useLanguages' => true,
      ),
      'google_recaptcha_site_key' => array(
        'type' => 'text',
        'label' => 'Google reCAPTCHA Site Key',
        'value' => '',
        'columnWidth' => 50,
      ),
      'google_recaptcha_secret_key' => array(
        'type' => 'text',
        'label' => 'Google reCAPTCHA Secret Key',
        'value' => '',
        'columnWidth' => 50,
      )
  );

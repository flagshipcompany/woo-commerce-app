<?php

require_once __DIR__.'/class.flagship-api-hooks.php';

class Flagship_Settings_Filters extends Flagship_Api_Hooks
{
    protected $type = 'filter';
    protected $count = 0;

    public function bootstrap()
    {
        // validate settings before save
        $this->add('woocommerce_settings_api_sanitized_fields_flagship_shipping_method');
        $this->add('settings_sanitized_fields_enabled');
        $this->add('settings_sanitized_fields_phone');
        $this->add('settings_sanitized_fields_address');
        $this->add('settings_sanitized_fields_shipper_credentials');

        $this->add('settings_sanitized_fields_integrity');

        // we need to include html, thus remove tag sanitizer
        $this->external->remove('woocommerce_shipping_rate_label', 'sanitize_text_field');
    }

    public function woocommerce_settings_api_sanitized_fields_flagship_shipping_method_filter($sanitized_fields)
    {
        $sanitized_fields = $this->on('settings_sanitized_fields_enabled', array($sanitized_fields));
        $sanitized_fields = $this->on('settings_sanitized_fields_phone', array($sanitized_fields));
        $sanitized_fields = $this->on('settings_sanitized_fields_address', array($sanitized_fields));
        $sanitized_fields = $this->on('settings_sanitized_fields_shipper_credentials', array($sanitized_fields));

        $this->on('settings_sanitized_fields_integrity', array($sanitized_fields));

        return $sanitized_fields;
    }

    // custom filters
    public function settings_sanitized_fields_phone_filter($sanitized_fields)
    {
        if ($errors = $this->ctx['validation']->phone($sanitized_fields['shipper_phone_number'])) {
            $this->ctx['notification']->warning($errors);
        }

        return $sanitized_fields;
    }

    public function settings_sanitized_fields_enabled_filter($sanitized_fields)
    {
        if ($sanitized_fields['enabled'] != 'yes') {
            $this->ctx['notification']->warning(__('FlagShip Shipping is disabled.', FLAGSHIP_SHIPPING_TEXT_DOMAIN));
        }

        return $sanitized_fields;
    }

    public function settings_sanitized_fields_address_filter($sanitized_fields)
    {
        // if user set/update token, we need to use the latest entered one
        if (isset($sanitized_fields['token'])) {
            $this->ctx['client']->set_token($sanitized_fields['token']);
        }

        $errors = $this->ctx['validation']->address(
            $sanitized_fields['origin'],
            $sanitized_fields['freight_shipper_state'],
            $sanitized_fields['freight_shipper_city']
        );

        // address correction
        if ($errors && isset($errors['content'])) {
            $sanitized_fields['origin'] = $errors['content']['postal_code'];
            $sanitized_fields['freight_shipper_state'] = $errors['content']['state'];
            $sanitized_fields['freight_shipper_city'] = $errors['content']['city'];

            $this->ctx['notification']->warning(__('Address corrected to match with shipper\'s postal code.', FLAGSHIP_SHIPPING_TEXT_DOMAIN));

            $errors = array();
        }

        if ($errors) {
            $this->ctx['notification']->warning($errors);
        }

        return $sanitized_fields;
    }

    public function settings_sanitized_fields_shipper_credentials_filter($sanitized_fields)
    {
        if (!$sanitized_fields['shipper_person_name']) {
            $this->ctx['notification']->warning(__('Shipper person name is missing.', FLAGSHIP_SHIPPING_TEXT_DOMAIN));
        }

        if (!$sanitized_fields['shipper_company_name']) {
            $this->ctx['notification']->warning(__('Shipper company name is missing.', FLAGSHIP_SHIPPING_TEXT_DOMAIN));
        }

        if (!$sanitized_fields['shipper_phone_number']) {
            $this->ctx['notification']->warning(__('Shipper phone number is missing.', FLAGSHIP_SHIPPING_TEXT_DOMAIN));
        }

        if (!$sanitized_fields['freight_shipper_street']) {
            $this->ctx['notification']->warning(__('Shipper address\'s streetline is missing.', FLAGSHIP_SHIPPING_TEXT_DOMAIN));
        }

        return $sanitized_fields;
    }

    public function settings_sanitized_fields_integrity_filter($sanitized_fields)
    {
        if (isset($sanitized_fields['token'])) {
            $this->ctx['client']->set_token($sanitized_fields['token']);
        }

        $errors = $this->ctx['validation']->settings($sanitized_fields);

        if ($errors) {
            $this->ctx['notification']->error(__('<strong>Shipping Integrity Failure:</strong> <br/>', FLAGSHIP_SHIPPING_TEXT_DOMAIN));
            $this->ctx['notification']->reverse_order('error');
        }

        return $sanitized_fields;
    }
}
<?php

require_once __DIR__.'/class.flagship-api-hooks.php';

class Flagship_Setup_Actions extends Flagship_Api_Hooks
{
    protected $type = 'action';

    public function bootstrap()
    {
        $this->add('woocommerce_shipping_init');
        $this->add('init');
    }

    /**
     * Load shipping method.
     *
     * @param array $methods
     */
    public function woocommerce_shipping_init_action($methods)
    {
        if (!class_exists('Flagship_WC_Shipping_Method')) {
            include_once FLAGSHIP_SHIPPING_PLUGIN_DIR.'includes/class.flagship-wc-shipping-method.php';
        }
    }

    /**
     * Enable translation.
     */
    public function init_action()
    {
        load_plugin_textdomain(FLAGSHIP_SHIPPING_TEXT_DOMAIN, false, 'flagship-for-woocommerce/languages/');
    }
}
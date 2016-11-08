<?php

namespace FS\Configurations\WordPress\Shop;

class Order extends \FS\Components\Model\AbstractModel implements \ArrayAccess, \FS\Components\Shop\OrderInterface
{
    protected $nativeOrder;
    protected $cache = array();

    public function getId()
    {
        if (!isset($this->cache['id'])) {
            $this->cache['id'] = $this->getNativeOrder()->id;
        }

        return $this->cache['id'];
    }

    public function getShippingService()
    {
        if (!isset($this->cache['shippingService'])) {
            $methods = $this->getNativeOrder()->get_shipping_methods();
            $phrase = $methods[key($methods)]['method_id'];

            $this->cache['shippingService'] = self::parseShippingServicePhrase($phrase);
        }

        return $this->cache['shippingService'];
    }

    public function getShipment()
    {
        $raw = $this['flagship_shipping_raw'];

        if (!$raw) {
            return;
        }

        $factory = $this->getApplicationContext()->getComponent('\\FS\\Components\\Shop\\Factory\\ShopFactory');

        return $factory->getModel(
            \FS\Components\Shop\Factory\FactoryInterface::RESOURCE_SHIPMENT,
            array('raw' => $raw)
        )->setReceiverAddress($this->getReceiverAddress());
    }

    public function getReceiverAddress()
    {
        if (!isset($this->cache['to_address'])) {
            $this->cache['to_address'] = array(
                'name' => $this->getNativeOrder()->shipping_company,
                'attn' => $this->getNativeOrder()->shipping_first_name.' '.$this->getNativeOrder()->shipping_last_name,
                'address' => trim($this->getNativeOrder()->shipping_address_1.' '.$this->getNativeOrder()->shipping_address_2),
                'city' => $this->getNativeOrder()->shipping_city,
                'state' => $this->getNativeOrder()->shipping_state,
                'country' => $this->getNativeOrder()->shipping_country,
                'postal_code' => $this->getNativeOrder()->shipping_postcode,
                'phone' => $this->getNativeOrder()->billing_phone, // no such a field in the shipping!?
            );
        }

        return $this->cache['to_address'];
    }

    public function isInternational()
    {
        return $this->getNativeOrder()->shipping_country != 'CA';
    }

    public function hasQuote()
    {
        $settings = $this->getApplicationContext()
            ->getComponent('\\FS\\Components\\Settings');
        $service = $this->getShippingService();

        return $service['provider'] == $settings['FLAGSHIP_SHIPPING_PLUGIN_ID'];
    }

    // order interface
    public function setNativeOrder($nativeOrder)
    {
        $this->nativeOrder = $nativeOrder;

        return $this;
    }

    public function getNativeOrder()
    {
        return $this->nativeOrder;
    }

    // array access methods [meta data]
    public function offsetSet($offset, $value)
    {
        // fix double quote slash
        if (is_string($value)) {
            $value = wp_slash($value);
        }

        update_post_meta($this->getId(), $offset, $value);
    }

    public function offsetExists($offset)
    {
        $existed = get_post_meta($this->getId(), $offset, true);

        return empty($existed);
    }

    public function offsetUnset($offset)
    {
        delete_post_meta($this->getId(), $offset);
    }

    public function offsetGet($offset)
    {
        return get_post_meta($this->getId(), $offset, true);
    }

    // static methods
    public static function parseShippingServicePhrase($phrase)
    {
        $methodsArray = explode('|', $phrase);
        $instanceId;

        if (count($methodsArray) == 6) {
            list($provider, $courier_name, $courier_code, $courier_desc, $date, $instanceId) = $methodsArray;
        } else {
            list($provider, $courier_name, $courier_code, $courier_desc, $date) = $methodsArray;
        }

        $service = array(
            'provider' => $provider,
            'courier_name' => strtolower($courier_name),
            'courier_code' => $courier_code,
            'courier_desc' => $courier_desc,
            'date' => $date,
            'instance_id' => isset($instanceId) ? $instanceId : 0,
        );

        return $service;
    }
}
<?php

namespace FS\Shipping\RequestBuilder;

class RequestBuilderTestCase extends \FlagshipShippingUnitTestCase
{
    protected $package;
    protected $order;

    public function setUp()
    {
        parent::setUp();

        $this->package = require __DIR__.'/../../fixtures/Package.php';
        $this->order = $this->getApplicationContext()
            ->getComponent('\\FS\\Components\\Order\\ShoppingOrder')
            ->setWcOrder(\FlagshipShippingWooCommerceFactory::createSimpleOrder());

        foreach ($this->package['contents'] as $key => $package) {
            $this->package['contents'][$key]['data'] = \FlagshipShippingWooCommerceFactory::createSimpleProduct();
        }
    }

    public function testShipperAddressRequestBuilder()
    {
        $builder = new \FS\Components\Shipping\RequestBuilder\ShipperAddressBuilder();
        $options = $this->ctx->getComponent('\\FS\\Components\\Options');

        $this->assertSame(array(
            'country' => 'CA',
            'state' => 'QC',
            'city' => 'POINTE-CLAIRE',
            'postal_code' => 'H9R5P9',
            'address' => '148 Brunswick',
            'name' => 'FlagShip WooCommerce Test App',
            'attn' => 'FlagShip Tester',
            'phone' => '+1 866 320 8383',
            'ext' => '',
        ), $builder->build($options));
    }

    public function testShoppingCartPackageItemsBuilder()
    {
        $builder = $this->getApplicationContext()->getComponent('\\FS\\Components\\Shipping\\RequestBuilder\\ShoppingCart\\PackageItemsBuilder');

        $this->assertSame(array(
            'items' => array(
                0 => array(
                    'width' => 1,
                    'height' => 1,
                    'length' => 1,
                    'weight' => 2,
                    'description' => 'Flagship shipping package',
                ),
            ),
            'units' => 'imperial',
            'type' => 'package',
        ), $builder->build(array(
            'package' => $this->package,
            'options' => $this->ctx->getComponent('\\FS\\Components\\Options'),
        )));
    }

    public function testShoppingCartReceiverAddressBuilder()
    {
        $builder = $this->getApplicationContext()->getComponent('\\FS\\Components\\Shipping\\RequestBuilder\\ShoppingCart\\ReceiverAddressBuilder');

        $this->assertSame(array(
            'country' => 'CA',
            'state' => 'QC',
            'city' => 'Verdun',
            'postal_code' => 'H3E 1H2',
            'address' => '1460 N. MAIN STREET, # 9 ',
        ), $builder->build($this->package));
    }

    public function testShoppingOrderPackageItemsBuilder()
    {
        $builder = $this->getApplicationContext()->getComponent('\\FS\\Components\\Shipping\\RequestBuilder\\ShoppingOrder\\PackageItemsBuilder');

        $this->assertSame(array(
            'items' => array(
                0 => array(
                    'width' => 1,
                    'height' => 1,
                    'length' => 1,
                    'weight' => 4,
                    'description' => 'Flagship shipping package',
                ),
            ),
            'units' => 'imperial',
            'type' => 'package',
        ), $builder->build(array(
            'order' => $this->order,
            'options' => $this->ctx->getComponent('\\FS\\Components\\Options'),
        )));
    }

    public function testShoppingOrderReceiverAddressBuilder()
    {
        $builder = $this->getApplicationContext()->getComponent('\\FS\\Components\\Shipping\\RequestBuilder\\ShoppingOrder\\ReceiverAddressBuilder');

        $this->assertSame(array(
            'name' => 'WooCompany',
            'attn' => 'Jeroen Sormani',
            'address' => 'WooAddress',
            'city' => 'WooCity',
            'state' => 'NY',
            'country' => 'US',
            'postal_code' => '123456',
            'phone' => '',
        ), $builder->build(array(
            'order' => $this->order,
        )));
    }
}
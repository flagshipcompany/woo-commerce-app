<?php

namespace FS\Components\Shipping\Factory;

class ShoppingOrderRateRequestFactory extends AbstractRequestFactory implements FormattedRequestInterface
{
    public function makeRequest(FormattedRequestInterface $request, \FS\Components\Shipping\RequestBuilder\Factory\RequestBuilderFactory $factory)
    {
        $request->setRequestPart(
            'from',
            $this->makeRequestPart(
                $factory->getBuilder('ShipperAddress', array(
                    'type' => 'order',
                )),
                $this->payload
            )
        );

        $toAddress = $this->makeRequestPart(
            $factory->getBuilder('ReceiverAddress', array(
                'type' => 'order',
            )),
            $this->payload
        );

        $request->setRequestPart(
            'to',
            $toAddress
        );

        $request->setRequestPart(
            'packages',
            $this->makeRequestPart(
                $factory->getBuilder('PackageItems', array(
                    'type' => 'order',
                    'usePackingApi' => $this->payload['options']->equal('enable_packing_api', 'yes'),
                )),
                $this->payload
            )
        );

        $request->setRequestPart(
            'payment',
            array(
                'payer' => 'F',
            )
        );

        // validate north american address
        if (in_array($toAddress['country'], array('CA', 'US'))) {
            $request->setRequestpart(
                'options',
                array(
                    'address_correction' => true,
                )
            );
        }

        return $request;
    }
}

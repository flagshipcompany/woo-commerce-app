<?php

namespace FS\Components\Shipping\Controller;

use FS\Components\AbstractComponent;
use FS\Components\Shipping\Object\Shipping;
use FS\Components\Web\RequestParam as Req;
use FS\Context\ApplicationContext as App;

class MetaboxController extends AbstractComponent
{
    public function display(Req $request, App $context, Shipping $shipping)
    {
        $view = $context
            ->_('\\FS\\Components\\View\\Factory\\ViewFactory')
            ->resolve(\FS\Components\View\Factory\ViewFactory::RESOURCE_METABOX);

        $shipment = $shipping->getShipment();
        $service = $shipping->getService();

        // shipment created
        if ($shipment->isCreated()) {
            return $view->render([
                'type' => 'created',
                'shipment' => $shipment->toArray(),
            ]);
        }

        $payload = [];

        // quoted but no shipment created
        if (!$shipment->isCreated() && $shipping->isFlagShipRateChoosen()) {
            $payload['type'] = 'create';
            $payload['service'] = $service;
            $payload['cod'] = [
                'currency' => strtoupper(\get_woocommerce_currency()),
            ];
        }

        // possibly not quoted with FS
        if (!isset($payload['type'])) {
            $payload['type'] = 'unavailable';
        }

        // requotes
        if ($requotes = $shipping->getOrder()->getAttribute('flagship_shipping_requote_rates')) {
            $payload['requote_rates'] = $requotes;
        }

        $view->render($payload);
    }

    public function createShipment(Req $request, App $context, Shipping $shipping)
    {
        $shipment = $shipping->getShipment();

        if ($shipment->isCreated()) {
            $context->alert(sprintf('You have flagship shipment for this order. FlagShip ID (%s)', $shipment->getId()), 'warning');

            return $this;
        }

        $order = $shipping->getOrder();

        $factory = $context
            ->_('\\FS\\Components\\Shipping\\Request\\Factory\\ShoppingOrderConfirmation');

        $response = $context->command()->confirm(
            $context->api(),
            $factory->setPayload([
                'shipping' => $shipping,
                'request' => $request,
                'options' => $context->option(),
            ])->getRequest()
        );

        if (!$response->isSuccessful()) {
            return;
        }

        $order->removeAttribute('flagship_shipping_requote_rates');

        $shipment->set($response->getContent());

        $shipping->save([
            'save_meta_keys' => true,
        ]);
    }

    public function voidShipment(Req $request, App $context, Shipping $shipping)
    {
        $shipment = $shipping->getShipment();

        if (!$shipment->isCreated()) {
            $context->alert(sprintf('Unable to access shipment with FlagShip ID (%s)', $shipment->getId()), 'warning');

            return;
        }

        $order = $shipping->getOrder();

        $response = $context->api()->delete('/ship/shipments/'.$shipment->getId());

        if (!$response->isSuccessful()) {
            $context->alert(sprintf('Unable to void shipment with FlagShip ID (%s)', $shipment->getId()), 'warning');

            return;
        }

        if (!$shipment->hasPickup()) {
            $order->removeAttribute('flagship_shipping_raw');

            return;
        }

        $this->voidPickup($request, $context, $shipping);

        $order->removeAttribute('flagship_shipping_raw');
    }

    public function requoteShipment(Req $request, App $context, Shipping $shipping)
    {
        $factory = $context
            ->_('\\FS\\Components\\Shipping\\Request\\Factory\\ShoppingOrderRate');
        $rateProcessorFactory = $context
            ->_('\\FS\\Components\\Shipping\\RateProcessor\\Factory\\RateProcessorFactory');

        $response = $context->command()->quote(
            $context->api(),
            $factory->setPayload([
                'shipping' => $shipping,
                'options' => $context->option(),
            ])->getRequest()
        );

        if (!$response->isSuccessful()) {
            $context->alert('Flagship Shipping has some difficulty in retrieving the rates. Please contact site administrator for assistance.<br/>', 'error');

            return;
        }

        $service = $shipping->getService();

        $rates = $response->getContent();

        $rates = $rateProcessorFactory
            ->resolve('ProcessRate')
            ->getProcessedRates($rates, [
                'factory' => $rateProcessorFactory,
                'options' => $context->option(),
                'instanceId' => $service['instance_id'] ? $service['instance_id'] : false,
                'methodId' => $context->setting('FLAGSHIP_SHIPPING_PLUGIN_ID'),
            ]);

        $wcShippingRates = [];

        foreach ($rates as $rate) {
            $wcShippingRates[$rate['id']] = $rate['label'].' $'.$rate['cost'];
        }

        if ($wcShippingRates) {
            $shipping->getOrder()->setAttribute('flagship_shipping_requote_rates', $wcShippingRates);
        }
    }

    public function schedulePickup(Req $request, App $context, Shipping $shipping)
    {
        $factory = $context
            ->_('\\FS\\Components\\Shipping\\Request\\Factory\\ShoppingOrderPickup');

        $shipment = $shipping->getShipment();

        if (!$shipment->isCreated()) {
            return;
        }

        $response = $context->command()->pickup(
            $context->api(),
            $factory->setPayload([
                'options' => $context->option(),
                'shipping' => $shipping,
                'date' => $request->request->get('flagship_shipping_pickup_schedule_date', date('Y-m-d')),
            ])->getRequest()
        );

        if (!$response->isSuccessful()) {
            $context->alert(sprintf('Unable to schedule pick-up with FlagShip ID (%s)', $shipment->getId()), 'warning');

            return;
        }

        $shipment->set('pickup', $response->getContent());

        $shipping->save();
    }

    public function voidPickup(Req $request, App $context, Shipping $shipping)
    {
        $pickup = $shipping->getPickup();

        if (!$pickup->isCreated()) {
            return;
        }

        $response = $context->api()->delete('/pickups/'.$pickup->getId());

        if (!$response->isSuccessful()) {
            $context->alert(sprintf('Unable to void pick-up with FlagShip Pickup ID (%s)', $pickup->getId()), 'warning');

            return;
        }

        $shipping->getShipment()->remove('pickup');

        $shipping->save();
    }
}
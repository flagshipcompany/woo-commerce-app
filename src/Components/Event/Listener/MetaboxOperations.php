<?php

namespace FS\Components\Event\Listener;

use FS\Components\AbstractComponent;
use FS\Context\ApplicationListenerInterface;
use FS\Components\Event\NativeHookInterface;
use FS\Context\ConfigurableApplicationContextInterface as Context;
use FS\Context\ApplicationEventInterface as Event;
use FS\Components\Event\ApplicationEvent;

class MetaboxOperations extends AbstractComponent implements ApplicationListenerInterface, NativeHookInterface
{
    public function getSupportedEvent()
    {
        return ApplicationEvent::METABOX_OPERATIONS;
    }

    public function onApplicationEvent(Event $event, Context $context)
    {
        $rp = $context
            ->_('\\FS\\Components\\Web\\RequestParam');
        $order = $event->getInput('order');

        $context
            ->controller('\\FS\\Components\\Shipping\\Controller\\MetaboxController', [
                'shipment-create' => 'createShipment',
                'shipment-void' => 'voidShipment',
                'shipment-requote' => 'requoteShipment',
                'pickup-schedule' => 'schedulePickup',
                'pickup-void' => 'voidPickup',
            ])
            ->before(function ($context) use ($order) {
                // apply middlware function before invoke controller method
                $context
                    ->_('\\FS\\Components\\Notifier')
                    ->scope('shop_order', ['id' => $order->getId()]);

                // load instance shipping method used by this shopping order
                $service = $order->getShippingService();

                $option = $context
                    ->option()
                    ->sync($service['instance_id'] ? $service['instance_id'] : false);

                $context
                    ->api()
                    ->setToken($option->get('token'));
            })
            ->dispatch($rp->request->get('flagship_shipping_shipment_action'), [$order]);
    }

    public function publishNativeHook(Context $context)
    {
        \add_action('woocommerce_process_shop_order_meta', function ($postId, $post) use ($context) {
            $event = new ApplicationEvent(ApplicationEvent::METABOX_OPERATIONS);
            $order = $context->_('\\FS\\Components\\Shop\\Factory\\ShopFactory')->resolve('order', array(
                'id' => $postId,
            ));

            $event->setInputs(array('order' => $order));

            $context->publishEvent($event);
        }, 10, 2);

        return $this;
    }

    public function getNativeHookType()
    {
        return self::TYPE_ACTION;
    }
}

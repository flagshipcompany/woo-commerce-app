<?php

namespace FS\Components\Validation\Factory;

class ValidatorFactory extends \FS\Components\AbstractComponent implements FactoryInterface, \FS\Components\Factory\DriverAwareInterface
{
    protected $driver;

    public function getValidator($resource, $context = array())
    {
        $validator = $this->getFactoryDriver()->getValidator($resource, $context);

        if ($validator) {
            return $validator->setApplicationContext($this->getApplicationContext());
        }

        throw new \Exception('Unable to resolve validator: '.$resource, 500);
    }

    public function setFactoryDriver(\FS\Components\Factory\DriverInterface $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    public function getFactoryDriver()
    {
        return $this->driver;
    }
}
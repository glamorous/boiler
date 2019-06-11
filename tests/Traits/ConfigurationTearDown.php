<?php

namespace Glamorous\Boiler\Tests\Traits;

use Glamorous\Boiler\Helpers\Configuration;
use ReflectionClass;

trait ConfigurationTearDown
{
    public function tearDown()
    {
        $reflection = new ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue($reflection, null);
        $property->setAccessible(false);
    }
}

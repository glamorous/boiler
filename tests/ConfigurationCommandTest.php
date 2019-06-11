<?php

namespace Glamorous\Boiler\Tests;

use Glamorous\Boiler\ConfigurationCommand;
use Glamorous\Boiler\Helpers\Configuration;
use Glamorous\Boiler\Tests\Traits\ConfigurationTearDown;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ConfigurationCommandTest extends TestCase
{
    use ConfigurationTearDown;

    public function test_that_configuration_is_set_by_construct()
    {
        $testClass = new class extends ConfigurationCommand {
        };
        $class = new ReflectionClass(ConfigurationCommand::class);
        $property = $class->getProperty('configuration');
        $property->setAccessible(true);
        $configuration = $property->getValue($testClass);

        static::assertInstanceOf(Configuration::class, $configuration);
    }
}

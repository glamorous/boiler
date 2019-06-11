<?php

namespace Glamorous\Boiler;

use Glamorous\Boiler\Helpers\Configuration;
use Symfony\Component\Console\Command\Command;

abstract class ConfigurationCommand extends Command
{
    /**
     * Singleton instance of Configuration
     *
     * @var Configuration
     */
    protected $configuration;

    /**
     * Constructor
     *
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     */
    public function __construct(string $name = null)
    {
        $this->configuration = $this->getConfiguration();

        parent::__construct($name);
    }

    final private function getConfiguration(): Configuration
    {
        return Configuration::getInstance();
    }
}

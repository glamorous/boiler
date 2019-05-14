<?php

namespace Glamorous\Boiler\Tests;

use Glamorous\Boiler\GetPathsCommand;
use Glamorous\Boiler\Tests\Traits\ConfigurationTearDown;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GetPathsCommandTest extends TestCase
{
    use ConfigurationTearDown;

    public function test_execute_get_paths_command_returns_paths()
    {
        $command = $this->getCommandWithChangedConfiguration(GetPathsCommand::class, ['my/path']);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('Path', $display);
        static::assertStringContainsString('my/path', $display);
    }

    public function test_execute_get_paths_command_returns_no_paths_message()
    {
        $command = $this->getCommandWithChangedConfiguration(GetPathsCommand::class, []);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertEquals('No paths set...', trim($display));
    }

    /**
     * Get initialized command with overrided configuration.
     *
     * @param string $commandName
     * @param array $paths
     *
     * @return Command
     *
     * @throws ReflectionException
     */
    protected function getCommandWithChangedConfiguration(string $commandName, array $paths): Command
    {
        $command = new $commandName();

        $configuration = new class($paths)
        {
            /**
             * Paths to return.
             *
             * @var array
             */
            private $paths = [];

            public function __construct($paths)
            {
                $this->paths = $paths;
            }

            public function getPaths(): array
            {
                return $this->paths;
            }
        };

        $stub = new ReflectionClass($commandName);
        $property = $stub->getProperty('configuration');
        $property->setAccessible(true);
        $property->setValue($command, $configuration);

        return $command;
    }
}

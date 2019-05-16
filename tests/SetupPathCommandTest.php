<?php

namespace Glamorous\Boiler\Tests;

use Glamorous\Boiler\BoilerException;
use Glamorous\Boiler\SetupPathCommand;
use Glamorous\Boiler\Tests\Traits\SetupRealFilesystemAndDefaultConfig;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SetupPathCommandTest extends TestCase
{
    use SetupRealFilesystemAndDefaultConfig;

    public function test_that_execute_throws_exception_if_folder_not_exits()
    {
        static::expectException(BoilerException::class);
        static::expectExceptionMessage('Directory does not exists');

        $command = new SetupPathCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute(['directory' => 'folder/does/not/exists']);
    }

    public function test_that_execute_throws_exception_if_path_already_in_configuration()
    {
        static::expectException(BoilerException::class);
        static::expectExceptionMessage('Folder already added.');

        $command = $this->getCommandWithChangedConfiguration(SetupPathCommand::class, [$this->pathToAdd], false);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['directory' => $this->pathToAdd]);
    }

    public function test_that_execute_will_show_error_message_if_setup_failed()
    {
        $command = $this->getCommandWithChangedConfiguration(SetupPathCommand::class, [], false);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['directory' => $this->pathToAdd]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('Directory could not be added.', trim($display));
    }

    public function test_that_execute_will_show_success_message_if_setup_succeeded()
    {
        $command = $this->getCommandWithChangedConfiguration(SetupPathCommand::class, [], true);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['directory' => $this->pathToAdd]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('Directory successfully added', trim($display));
    }

    /**
     * Get initialized command with overrided configuration.
     *
     * @param string $commandName
     * @param array $paths
     * @param bool $returnValue
     *
     * @return Command
     *
     * @throws ReflectionException
     */
    private function getCommandWithChangedConfiguration(
        string $commandName,
        array $paths,
        bool $returnValue = false
    ): Command {
        $command = new $commandName();

        $configuration = new class($paths, $returnValue)
        {
            /**
             * Paths to return.
             *
             * @var array
             */
            private $paths = [];

            /**
             * Boolean to return for addPath.
             *
             * @var boolean
             */
            private $returnValue;

            public function __construct($paths, $returnValue)
            {
                $this->paths = $paths;
                $this->returnValue = $returnValue;
            }

            public function getPaths(): array
            {
                return $this->paths;
            }

            public function addPath($path): bool
            {
                return $this->returnValue;
            }
        };

        $stub = new ReflectionClass($commandName);
        $property = $stub->getProperty('configuration');
        $property->setAccessible(true);
        $property->setValue($command, $configuration);

        return $command;
    }
}

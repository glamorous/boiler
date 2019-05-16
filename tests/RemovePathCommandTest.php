<?php

namespace Glamorous\Boiler\Tests;

use Glamorous\Boiler\BoilerException;
use Glamorous\Boiler\RemovePathCommand;
use Glamorous\Boiler\Tests\Traits\SetupRealFilesystemAndDefaultConfig;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RemovePathCommandTest extends TestCase
{
    use SetupRealFilesystemAndDefaultConfig;

    public function test_that_execute_throws_exception_if_folder_not_exits()
    {
        static::expectException(BoilerException::class);
        static::expectExceptionMessage('Directory does not exists');

        $command = new RemovePathCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute(['directory' => 'folder/does/not/exists']);
    }

    public function test_that_execute_throws_exception_if_path_not_in_configuration()
    {
        static::expectException(BoilerException::class);
        static::expectExceptionMessage('Folder not in paths.');

        $command = new RemovePathCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute(['directory' => $this->pathToRemove]);
    }

    public function test_that_execute_will_show_error_message_if_remove_failed()
    {
        $command = $this->getCommandWithChangedConfiguration(RemovePathCommand::class, [$this->pathToRemove], false);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['directory' => $this->pathToRemove]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('Directory could not be removed', trim($display));
    }

    public function test_that_execute_will_show_success_message_if_remove_succeeded()
    {
        $command = $this->getCommandWithChangedConfiguration(RemovePathCommand::class, [$this->pathToRemove], true);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['directory' => $this->pathToRemove]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('Directory successfully removed', trim($display));
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
             * Boolean to return for removePath.
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

            public function removePath($path): bool
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

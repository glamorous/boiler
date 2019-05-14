<?php

namespace Glamorous\Boiler\Tests;

use Glamorous\Boiler\RunCommand;
use Glamorous\Boiler\Tests\Traits\ConfigurationTearDown;
use Glamorous\Boiler\Tests\Traits\SetupFakeFilesystemAndDefaultConfig;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends TestCase
{
    use SetupFakeFilesystemAndDefaultConfig, ConfigurationTearDown;

    public function test_error_is_shown_if_no_paths_are_configured()
    {
        $output = $this->executeAndReturnOutput([], ['template' => 'my-template']);
        static::assertStringContainsString('No paths configured', $output);
    }

    public function test_error_is_shown_if_template_is_not_found()
    {
        $output = $this->executeAndReturnOutput([$this->pathToAdd], ['template' => 'my-template']);
        static::assertStringContainsString('No template found with name', $output);
    }

    public function test_error_is_show_if_template_could_not_be_parsed()
    {
        static::markTestSkipped();
        // Template file `' . $path . '` could not be parsed'.
    }

    public function test_error_is_shown_if_template_was_not_valid()
    {
        static::markTestSkipped();
        // Template could not be parsed as a boiler template.
    }

    public function test_error_is_shown_if_folder_already_exists_and_no_directory_is_given()
    {
        static::markTestSkipped();
        $output = $this->executeAndReturnOutput([$this->pathToAdd], ['template' => 'my-template']);
        static::assertStringContainsString('Folder already exists', $output);
    }

    public function test_error_is_shown_if_folder_already_exists_and_directory_is_given()
    {
        static::markTestSkipped();
        $output = $this->executeAndReturnOutput([$this->pathToAdd], ['template' => 'my-template']);
        static::assertStringContainsString('Folder already exists', $output);
    }

    /**
     * Get output of the command with overrided configuration paths and arguments for command.
     *
     * @param array $paths
     * @param array $arguments
     *
     * @return string
     *
     * @throws ReflectionException
     */
    private function executeAndReturnOutput(array $paths, array $arguments): string
    {
        $command = new RunCommand();

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

        $stub = new ReflectionClass(RunCommand::class);
        $property = $stub->getProperty('configuration');
        $property->setAccessible(true);
        $property->setValue($command, $configuration);

        $commandTester = new CommandTester($command);
        $commandTester->execute($arguments);

        return $commandTester->getDisplay();
    }
}

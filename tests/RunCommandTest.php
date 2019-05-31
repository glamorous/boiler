<?php

namespace Glamorous\Boiler\Tests;

use Glamorous\Boiler\RunCommand;
use Glamorous\Boiler\Tests\Traits\SetupRealFilesystemAndDefaultConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends TestCase
{
    use SetupRealFilesystemAndDefaultConfig;

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
        $this->assertOutputStringWithGivenFile('unparseable', 'Template file located at `%s` could not be parsed');
    }

    public function test_error_is_shown_if_template_was_not_valid()
    {
        $this->assertOutputStringWithGivenFile('empty', 'Template could not be parsed as a yaml file');
    }

    public function test_error_is_shown_if_template_was_not_valid_and_missing_name()
    {
        $this->assertOutputStringWithGivenFile('invalid_name', 'There\'s no name defined in the template');
    }

    public function test_error_is_shown_if_template_was_not_valid_and_missing_steps()
    {
        $this->assertOutputStringWithGivenFile('invalid_steps', 'There are no steps defined in the template');
    }

    public function test_error_is_shown_if_template_was_not_valid_and_step_is_not_exists()
    {
        $this->assertOutputStringWithGivenFile(
            'invalid_step_not_exists',
            'The step `my_not_existing_step` does not exists.'
        );
    }

    public function test_error_is_shown_if_template_was_not_valid_and_step_has_no_name()
    {
        $this->assertOutputStringWithGivenFile(
            'invalid_no_name_for_step',
            'No `name` set for the step `step_without_name`'
        );
    }

    public function test_error_is_shown_if_template_was_not_valid_and_step_has_no_script()
    {
        $this->assertOutputStringWithGivenFile(
            'invalid_no_script_for_step',
            'No `script` set for the step `step_without_script`'
        );
    }

    public function test_error_is_shown_if_template_was_not_valid_and_include_is_invalid()
    {
        $this->assertOutputStringWithGivenFile('invalid_include', 'Include function must be an array.');
    }

    public function test_error_is_shown_if_template_was_not_valid_and_include_does_not_exists()
    {
        $this->assertOutputStringWithGivenFile(
            'invalid_include_not_exists',
            'Included file `not_existing_file` does not exists'
        );
    }

    public function test_error_is_shown_if_template_was_not_valid_and_include_file_is_empty()
    {
        $templateDirectory = vfsStream::url($this->folderLocation);
        $fileContents = file_get_contents(__DIR__ . '/stubs/empty.yml');
        $filePath = $templateDirectory . '/empty.yml';
        file_put_contents($filePath, $fileContents);
        $this->assertOutputStringWithGivenFile('invalid_include_empty', 'Included file `empty` cannot be parsed');
    }

    public function test_error_is_shown_if_folder_already_exists_and_no_directory_is_given()
    {
        static::assertTrue(mkdir($this->pathToRunCommand . '/valid'));
        chdir($this->pathToRunCommand);
        $this->assertOutputStringWithGivenFile('valid', 'Folder already exists');
    }

    public function test_error_is_shown_if_folder_already_exists_and_directory_is_given()
    {
        static::assertTrue(mkdir($this->pathToRunCommand . '/given-directory'));
        chdir($this->pathToRunCommand);
        $this->assertOutputStringWithGivenFile('valid', 'Folder already exists', ['--dir' => 'given-directory']);
    }

    public function test_execute_will_execute_all_steps_from_valid_template()
    {
        chdir($this->pathToRunCommand);
        $output = $this->assertOutputStringWithGivenFile('valid', 'Installing Stub valid test');
        static::assertStringContainsString('Executing My valid first step', $output);
        static::assertStringContainsString('Executing My valid second step', $output);
        $createdDirectory = $this->pathToRunCommand . '/valid';
        static::assertDirectoryExists($createdDirectory);
        static::assertFileExists($createdDirectory . '/hello.txt');
        static::assertFileExists($createdDirectory . '/world.txt');
        static::assertEquals('hello', trim(file_get_contents($createdDirectory . '/hello.txt')));
        static::assertEquals('world-updated', trim(file_get_contents($createdDirectory . '/world.txt')));
    }

    public function test_that_included_files_works_and_get_executed_too()
    {
        $templateDirectory = vfsStream::url($this->folderLocation);
        $fileContents = file_get_contents(__DIR__ . '/stubs/to-include-valid.yml');
        $filePath = $templateDirectory . '/to-include-valid.yml';
        file_put_contents($filePath, $fileContents);
        chdir($this->pathToRunCommand);
        $output = $this->assertOutputStringWithGivenFile('valid-with-include', 'Installing Stub valid included test');
        static::assertStringContainsString('Executing My included valid step', $output);
        $createdDirectory = $this->pathToRunCommand . '/valid-with-include';
        static::assertFileExists($createdDirectory . '/step.txt');
        static::assertEquals('my included step', trim(file_get_contents($createdDirectory . '/step.txt')));
    }

    /**
     * Execute the command with a given template and test for specific output message and return output.
     *
     * @param string $filename
     * @param string $outputExpectation
     * @param array $arguments
     *
     * @return string
     *
     * @throws ReflectionException
     */
    private function assertOutputStringWithGivenFile(
        string $filename,
        string $outputExpectation,
        array $arguments = []
    ): string {
        $templateDirectory = vfsStream::url($this->folderLocation);
        $fileContents = file_get_contents(__DIR__ . '/stubs/' . $filename . '.yml');
        $filePath = $templateDirectory . '/' . $filename . '.yml';
        file_put_contents($filePath, $fileContents);
        $output = $this->executeAndReturnOutput(
            [$templateDirectory],
            array_merge(['template' => $filename], $arguments)
        );
        static::assertStringContainsString(sprintf($outputExpectation, $filePath), $output);

        return $output;
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

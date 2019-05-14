<?php

namespace Glamorous\Boiler;

use SplFileInfo;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class RunCommand extends ConfigurationCommand
{
    /**
     * Array with the paths.
     *
     * @var array
     */
    protected $paths;

    /**
     * Array with all possible variables.
     *
     * @var array
     */
    protected $variables = [];

    /**
     * Configure the command options.
     *
     * @throws BoilerException
     */
    protected function configure()
    {
        $this
            ->setName('create')
            ->setDescription('Create a project based on the given template')
            ->addArgument('template', InputArgument::REQUIRED)
            ->addOption(
                'dir',
                null,
                InputOption::VALUE_OPTIONAL,
                'Which name must the directory to create have?'
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_OPTIONAL,
                'Which name must the project have?'
            );
    }

    /**
     * Execute the command and catch exceptions.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        try {
            $this->executeCommand($input, $output);
        } catch (BoilerException $exception) {
            $output->write('<error>' . $exception->getMessage() . '</error>');
        }
    }

    /**
     * Search for the template file and return it if found.
     *
     * @param string $name
     *
     * @return null|SplFileInfo
     */
    protected function findTemplateFile(string $name): ?SplFileInfo
    {
        $name .= '.yml';
        $finder = new Finder();
        foreach ($this->paths as $directory) {
            $finder->files()->name('*.yml')->depth('== 0')->in($directory);

            foreach ($finder as $file) {
                if ($file->getFilename() === $name) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * Parse and load yml file from path.
     *
     * @param string $path
     *
     * @return mixed
     *
     * @throws BoilerException
     */
    protected function parseTemplateFile(string $path)
    {
        try {
            return Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new BoilerException(
                'Template file `' . $path . '` could not be parsed',
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Load included templates.
     *
     * @param array $template
     *
     * @return array
     * @throws BoilerException
     */
    protected function getIncludedTemplates(array $template): array
    {
        if (!array_key_exists('include', $template)) {
            return [];
        }

        if (!is_array($template['include'])) {
            throw new BoilerException('Include function must be an array.');
        }

        return array_map(function ($fileName) {
            $templateFile = $this->findTemplateFile($fileName);
            $template = ($templateFile !== null) ? $this->parseTemplateFile($templateFile->getRealPath()) : null;
            return ($template) ? $template : [];
        }, $template['include']);
    }

    /**
     * Validate the loaded template-file.
     *
     * @param array $template
     *
     * @throws BoilerException
     */
    protected function validateTemplate(array $template): void
    {
        if (!array_key_exists('name', $template) || empty($template['name'])) {
            throw new BoilerException('There\'s no name defined in the template');
        }

        if (!array_key_exists('steps', $template) || !is_array($template['steps']) || empty($template['steps'])) {
            throw new BoilerException('There are no steps defined in the template');
        }

        $this->validateSteps($template);
    }

    /**
     * Merge the extra template (if there are) with the main template.
     *
     * @param array $template
     * @param array $extraTemplates
     *
     * @return array
     */
    protected function mergeExtraTemplates(array $template, array $extraTemplates): array
    {
        foreach ($extraTemplates as $extraTemplate) {
            $template = array_merge($template, $extraTemplate);
        }

        return $template;
    }

    /**
     * Method to get a valid template from a given template name.
     *
     * @param string $templateFileName
     *
     * @return array
     * @throws BoilerException
     */
    protected function getValidTemplateArray(string $templateFileName): array
    {
        $templateFile = $this->findTemplateFile($templateFileName);

        if (is_null($templateFile)) {
            throw new BoilerException('No template found with name ' . $templateFileName);
        }

        $template = $this->parseTemplateFile($templateFile->getRealPath());

        if (!is_array($template)) {
            throw new BoilerException('Template could not be parsed as a boiler template');
        }

        $extraTemplates = $this->getIncludedTemplates($template);
        $template = $this->mergeExtraTemplates($template, $extraTemplates);

        $this->validateTemplate($template);

        return $template;
    }

    /**
     * Execute the scripts from the template.
     *
     * @param OutputInterface $output
     * @param array $template
     */
    protected function executeScripts(OutputInterface $output, array $template): void
    {
        $variables = [
            '{#PROJECT_NAME#}' => $this->variables['template_name'],
        ];

        foreach ($template['steps'] as $step) {
            $output->writeln('<info>Executing ' . $template[$step]['name'] . '</info>');
            $scripts = is_array($template[$step]['script']) ? $template[$step]['script'] : [$template[$step]['script']];
            foreach ($scripts as $script) {
                $script = str_replace(array_keys($variables), array_values($variables), $script);
                exec($script);
            }
        }
    }

    /**
     * Internal method to execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws BoilerException
     */
    private function executeCommand(InputInterface $input, OutputInterface $output): void
    {
        $this->paths = $this->configuration->getPaths();

        if (empty($this->paths)) {
            throw new BoilerException('No paths configured');
        }

        $templateFileName = $input->getArgument('template');
        $template = $this->getValidTemplateArray($templateFileName);

        $templateName = $input->getOption('name') ? $input->getOption('name') : $template['name'];
        $output->writeln('<info>Installing ' . $templateName . '</info>');
        $this->variables['template_name'] = $templateName;

        $directoryName = $input->getOption('dir') ? $input->getOption('dir') : $templateFileName;

        if (file_exists($directoryName)) {
            throw new BoilerException('Folder already exists');
        }
        mkdir($directoryName);
        chdir($directoryName);

        $this->executeScripts($output, $template);
    }

    /**
     * Internal method to validate the steps.
     *
     * @param array $template
     *
     * @throws BoilerException
     */
    private function validateSteps(array $template): void
    {
        $steps = $template['steps'];
        foreach ($steps as $step) {
            if (!array_key_exists($step, $template)) {
                throw new BoilerException('The step `' . $step . '` does not exists.');
            }

            if (!array_key_exists('name', $template[$step])) {
                throw new BoilerException('No `name` set for the step `' . $step . '`');
            }

            if (!array_key_exists('script', $template[$step])) {
                throw new BoilerException('No `script` set for the step `' . $step . '`');
            }
        }
    }
}

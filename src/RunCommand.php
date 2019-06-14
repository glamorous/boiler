<?php

namespace Glamorous\Boiler;

use Glamorous\Boiler\Helpers\Template;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

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
     * Replace variables in given string.
     *
     * @param string $contents
     *
     * @return string
     */
    private function replaceVariables(string $contents): string
    {
        $variables = [
            '{#PROJECT_NAME#}' => $this->variables['template_name'],
        ];

        return str_replace(array_keys($variables), array_values($variables), $contents);
    }

    /**
     * Execute the scripts from the template.
     *
     * @param OutputInterface $output
     * @param array $template
     */
    protected function executeScripts(OutputInterface $output, array $template): void
    {
        foreach ($template['steps'] as $step) {
            $output->writeln('<info>Executing ' . $template[$step]['name'] . '</info>');
            $scripts = is_array($template[$step]['script']) ? $template[$step]['script'] : [$template[$step]['script']];
            foreach ($scripts as $script) {
                exec($this->replaceVariables($script));
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

        if (is_array($templateFileName)) {
            throw new BoilerException('Only one template is allowed.');
        }

        $template = Template::getInstance()->searchTemplateByFilenameInGivenPaths($templateFileName, $this->paths);

        $templateName = $input->getOption('name') ? $input->getOption('name') : $template['name'];
        $directoryName = $input->getOption('dir') ?: $templateFileName;

        if (file_exists($directoryName)) {
            throw new BoilerException('Folder already exists');
        }

        $output->writeln('<info>Installing ' . $templateName . '</info>');
        $this->variables['template_name'] = $templateName;

        mkdir($directoryName);
        chdir($directoryName);

        $this->prepareTemplateFolder($templateFileName);

        $this->executeScripts($output, $template);
    }

    /**
     * Prepare the project directory by copying files from the template directory if available.
     *
     * @param string $name
     */
    protected function prepareTemplateFolder(string $name): void
    {
        $finder = new Finder();
        foreach ($this->paths as $directory) {
            $templateDir = $directory . '/' . $name;
            if (! is_dir($templateDir)) {
                continue;
            }

            $finder->in($templateDir)
                ->notName($name . '.yml')
                ->sortByType()
                ->ignoreDotFiles(false);

            foreach ($finder as $file) {
                if ($file->isDir()) {
                    mkdir($file->getFilename(), 0777, true);
                    continue;
                }

                if ($file->isFile()) {
                    copy($file->getPathname(), $file->getRelativePathname());
                    file_put_contents($file->getRelativePathname(), $this->replaceVariables($file->getContents()));
                    continue;
                }
            }
        }//end foreach
    }
}

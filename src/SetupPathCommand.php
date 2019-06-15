<?php

namespace Glamorous\Boiler;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupPathCommand extends ConfigurationCommand
{
    /**
     * Input to get arguments from.
     *
     * @var InputInterface
     */
    private $input;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('setup')
            ->setDescription('Set the directory as a place to search for boiler-templates.')
            ->addArgument('directory', InputArgument::OPTIONAL);
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws BoilerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        $directory = $this->getDirectory();
        $path = realpath($directory);

        if ($path === false || !file_exists($path)) {
            throw new BoilerException('Directory does not exists');
        }

        $paths = $this->configuration->getPaths();
        if (in_array($path, $paths)) {
            throw new BoilerException('Folder already added.');
        }
        $result = $this->configuration->addPath($path);

        $resultMessage = '<comment>Directory successfully added</comment>';

        if (!$result) {
            $resultMessage = '<error>Directory could not be added.</error>';
        }

        $output->writeln($resultMessage);
    }

    /**
     * Get the directory given (or current one).
     *
     * @return string
     *
     * @throws BoilerException
     */
    protected function getDirectory(): string
    {
        $directory = $this->input->getArgument('directory');

        if (is_array($directory)) {
            throw new BoilerException('Only one directory is allowed.');
        }

        if (empty($directory)) {
            $directory = getcwd();

            if ($directory === false) {
                throw new BoilerException('Current directory cannot be added.');
            }
        }

        return $directory;
    }
}

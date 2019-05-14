<?php

namespace Glamorous\Boiler;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemovePathCommand extends ConfigurationCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('remove')
            ->setDescription('Remove the directory as a place to search for boiler-templates.')
            ->addArgument('directory', InputArgument::REQUIRED);
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
        $path = $input->getArgument('directory');

        if (!file_exists($path)) {
            throw new BoilerException('Directory does not exists');
        }

        $paths = $this->configuration->getPaths();
        if (!in_array($path, $paths)) {
            throw new BoilerException('Folder not in paths.');
        }
        $result = $this->configuration->removePath($path);

        $resultMessage = '<comment>Directory successfully removed</comment>';

        if (!$result) {
            $resultMessage = '<error>Directory could not be removed</error>';
        }

        $output->writeln($resultMessage);
    }
}

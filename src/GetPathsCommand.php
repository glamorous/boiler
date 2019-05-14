<?php

namespace Glamorous\Boiler;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetPathsCommand extends ConfigurationCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('paths')
            ->setDescription('List all paths to search for templates');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $this->configuration->getPaths();

        if (empty($paths)) {
            $output->writeln('<info>No paths set...</info>');
            return;
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Path'])
            ->setRows(array_map(function ($path) {
                return [$path];
            }, $paths))
        ;
        $table->render();
    }
}

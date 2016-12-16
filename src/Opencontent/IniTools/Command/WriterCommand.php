<?php

namespace Opencontent\IniTools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Opencontent\IniTools\Tools\IniWriter;
use Symfony\Component\Console\Helper\Table;

class WriterCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('set')
            ->setDescription('Write ini variable')
            ->addArgument('group', InputArgument::REQUIRED, 'Ini group')
            ->addArgument('variable', InputArgument::REQUIRED, 'Ini variable')
            ->addArgument('set_value', InputArgument::REQUIRED, 'New value')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Ini file', 'site.ini')
            ->addOption('siteaccess', 's', InputOption::VALUE_REQUIRED, 'Siteaccess name')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append value')
            ->addOption('prepend', null, InputOption::VALUE_NONE, 'Prepend value')
            ->addOption('override', null, InputOption::VALUE_NONE, 'Set value in override directory file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $override = $input->getOption('override');
        $siteaccess = $input->getOption('siteaccess');
        $file = $input->getOption('file');
        $group = $input->getArgument('group');
        $variable = $input->getArgument('variable');
        $value = $input->getArgument('set_value');
        $append = $input->getOption('append');
        $prepend = $input->getOption('prepend');

        $ezRoot = getcwd();
        try {
            ReaderCommand::render(
                $output,
                $output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE,
                $ezRoot,
                $siteaccess,
                $file,
                $group,
                $variable
            );

            $writer = new IniWriter($ezRoot, $siteaccess);
            $result = $writer->setIni($file, $group, $variable, $value, $append, $prepend, $override);

            ReaderCommand::render(
                $output,
                $output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE,
                $ezRoot,
                $siteaccess,
                $file,
                $group,
                is_array($result) ? $variable : null
            );
        } catch (\RuntimeException $e) {
            $output->writeln($e->getMessage());
        }
    }

    protected function renderValue(OutputInterface $output, $data)
    {
        if (is_array($data)) {
            $this->renderArray($output, $data);
        } else {
            $output->writeln($data);
        }
    }

    protected function renderArray(OutputInterface $output, $data)
    {
        $table = new Table($output);
        $table->setHeaders(array('Name', 'Value'));
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = '(array)';
            }
            $table->addRow(array($key, $value));
        }
        $table->render();
    }
}
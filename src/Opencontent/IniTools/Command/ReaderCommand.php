<?php

namespace Opencontent\IniTools\Command;

use Opencontent\IniTools\Tools\ParsedIni;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Opencontent\IniTools\Tools\IniReader;
use Symfony\Component\Console\Helper\Table;
use Opencontent\IniTools\Tools\ParsedIniValue;

class ReaderCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('get')
            ->setDescription('Read ini variable')
            ->addArgument('group', InputArgument::OPTIONAL, 'Ini group')
            ->addArgument('variable', InputArgument::OPTIONAL, 'Ini variable')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Ini file', 'site.ini')
            ->addOption('siteaccess', 's', InputOption::VALUE_OPTIONAL, 'Siteaccess name')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Output format');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $siteaccess = $input->getOption('siteaccess');
        $file = $input->getOption('file');
        $format = $input->getOption('format');
        $group = $input->getArgument('group');
        $variable = $input->getArgument('variable');
        $ezRoot = getcwd();
        self::render(
            $output,
            $output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE,
            $ezRoot,
            $siteaccess,
            $file,
            $group,
            $variable,
            $format
        );
    }

    public static function render(OutputInterface $output, $verbose, $ezRoot, $siteaccess, $file, $group, $variable, $format = null)
    {
        try {
            $reader = new IniReader($ezRoot, $siteaccess);
            $ini = $reader->getIni($file);
            if ($verbose && ( $variable || $group )) {
                $ini = new ParsedIni($ini);
            }
            if ($variable) {
                $data = $ini->variable($group, $variable);
            } elseif ($group) {
                $data = $ini->group($group);
            } else {
                $data = array_keys($ini->groups());
            }
            self::renderValue($output, $data, $format);
        } catch (\RuntimeException $e) {
            $output->writeln($e->getMessage());
        }
    }

    protected static function renderValue(OutputInterface $output, $data, $format = null)
    {
        if ($data instanceof ParsedIniValue) {
            self::renderParsedIniValue($output, $data);
        } elseif (is_array($data)) {
            self::renderArray($output, $data, $format);
        } else {
            $output->writeln($data);
        }
    }

    protected static function renderArray(OutputInterface $output, $data, $format = null)
    {
        //$strings = array();
        $table = new Table($output);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = '(array)';
            }
            $strings[] = $key . ' => ' . $value;
            $table->addRow(array($key, $value));
        }
        if ( $format == 'string' )
            $output->writeln('[' . implode( ', ', $strings ) . ']');
        else
            $table->render();
    }

    protected static function renderParsedIniValue(OutputInterface $output, ParsedIniValue $value)
    {
        $table = new Table($output);
        $table->setHeaders($value->keys());
        foreach ($value->contents() as $content) {
            $table->addRow($content);
        }
        $table->render();
    }
}

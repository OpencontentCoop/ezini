<?php

namespace Opencontent\IniTools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;

class CopyCommand extends Command
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->filesystem = new Filesystem();
    }

    protected function configure()
    {
        $this
            ->setName('copy')
            ->setDescription('Copy file from file source to folder target')
            ->addArgument('source', InputArgument::REQUIRED, 'Siteaccess folder source')
            ->addArgument('target', InputArgument::REQUIRED, 'Siteaccess folder target');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source');
        $target = $input->getArgument('target');

        $siteAccessRoot = getcwd() . '/settings/siteaccess';
        $source = $siteAccessRoot . '/' . $source;
        $target = $siteAccessRoot . '/' . $target . '/' . basename( $source );

        if (!$this->filesystem->exists($source)) {
            throw new \RuntimeException("File $source not found");
        }

        if ($this->filesystem->exists($target)) {
            $this->filesystem->copy( $target, $target . '~', true );
        }

        $this->filesystem->copy( $source, $target, true );

    }
}
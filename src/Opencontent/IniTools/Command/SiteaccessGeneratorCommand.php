<?php

namespace Opencontent\IniTools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class SiteaccessGeneratorCommand extends Command
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    protected $saGroups = array('frontend', 'debug', 'backend');

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->filesystem = new Filesystem();
    }

    protected function configure()
    {
        $this
            ->setName('clone')
            ->setDescription('Generate siteaccess from template')
            ->addArgument('source', InputArgument::REQUIRED, 'Absolute path source siteaccess directory')
            ->addArgument('target', InputArgument::REQUIRED, 'Absolute path target siteaccess directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source');
        $target = $input->getArgument('target');

        if (!$this->filesystem->exists($source)) {
            throw new \RuntimeException("Directory $source not found");
        }

        if (!$this->filesystem->exists($target)) {
            $this->filesystem->mkdir($target);
            $output->writeln("New folder {$target}");
        }

        $sourceFinder = new Finder();
        $sourceFinder->files()->name('*.ini*')->in($source);

        /** @var SplFileInfo $file */
        foreach ($sourceFinder as $file) {
            $targetPath = $target . '/' . $file->getFilename();
            $this->filesystem->copy( $file->getPathname(), $targetPath);
            $output->writeln("New file {$targetPath}");
        }

    }
}
<?php

namespace Opencontent\IniTools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class SymlinkGeneratorCommand extends Command
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Finder
     */
    protected $finder;


    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->filesystem = new Filesystem();
    }

    protected function configure()
    {
        $this
            ->setName('symlink')
            ->setDescription('Generate siteaccess link from folder source to folder target')
            ->addArgument('source', InputArgument::REQUIRED, 'Siteaccess folder source')
            ->addArgument('target', InputArgument::REQUIRED, 'Siteaccess folder target');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source');
        $target = $input->getArgument('target');

        $siteAccessRoot = getcwd() . '/settings/siteaccess';
        $source = $siteAccessRoot . '/' . $source;
        $target = $siteAccessRoot . '/' . $target;

        if (!$this->filesystem->exists($source)) {
            throw new \RuntimeException("Directory $source not found");
        }

        if (!$this->filesystem->exists($target)) {
            $this->filesystem->mkdir($target);
            $output->writeln("New folder {$target}");
        }
        $sourceFinder = new Finder();
        $sourceFinder->files()->name('*.php')->in($source);

        /** @var SplFileInfo $file */
        foreach ($sourceFinder as $file) {
            $targetFinder = new Finder();
            $targetSearch = $targetFinder->in($target)->name($file->getFilename());

            if ($targetSearch->count() == 0) {
                $targetPath = $target . '/' . $file->getFilename();
                $sourcePath = $this->filesystem->makePathRelative(
                        $file->getPath(),
                        $target
                    ) . '/' . $file->getFilename();

                $this->filesystem->symlink(
                    $sourcePath,
                    $targetPath
                );

                $output->writeln("New symlink {$sourcePath}");
            }
        }
    }
}
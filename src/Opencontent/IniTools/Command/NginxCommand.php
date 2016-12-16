<?php

namespace Opencontent\IniTools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use RomanPitak\Nginx\Config\Scope;
use RomanPitak\Nginx\Config\Directive;

class NginxCommand extends Command
{

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Finder
     */
    protected $finder;

    protected $url;

    protected $documentRoot;

    protected $found = false;

    protected $matches = 0;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->filesystem = new Filesystem();
    }

    protected function configure()
    {
        $this
            ->setName('nginx')
            ->setDescription('Read nginx configurations')
            ->addOption(
                'url', 'u',
                InputOption::VALUE_REQUIRED, 'Server name (e.g. www.example.org)'
            )
            ->addOption(
                'document-root', 'r',
                InputOption::VALUE_REQUIRED, 'Document root  (defaul: /home/httpd/example/html )'
            )
            ->addOption(
                'dir', 'd',
                InputOption::VALUE_OPTIONAL, 'Configuration directory', '/etc/nginx/sites-enabled/'
            )
            ->addOption(
                'full-path', 'f',
                InputOption::VALUE_NONE, 'Return full filepath instead of filename'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getOption('dir');

        if (!$this->filesystem->exists($dir)) {
            throw new \RuntimeException("Directory $dir not found");
        }

        $sourceFinder = new Finder();
        $sourceFinder->files()->in($dir);

        $url = trim($input->getOption('url'), "''");
        $documentRoot = trim($input->getOption('document-root'), "''");
        $fullPath = $input->getOption('full-path');
        $this->matches = 0;

        if (!empty( $url )) {
            $this->matches++;
        } else {
            $url = null;
        }

        if (!empty( $documentRoot )) {
            $this->matches++;
        } else {
            $documentRoot = null;
        }


        $results = array();
        /** @var SplFileInfo $file */
        foreach ($sourceFinder as $file) {

            $path = $file->getPath() . $file->getFilename();
            $scope = Scope::fromFile($path);
            $this->found = 0;
            $this->walkScope($scope, $url, $documentRoot);
            if ($this->found >= $this->matches) {
                $results[] = $path;
            }
//            if ( $this->found > 0 )
//                $output->writeln($this->found . ' ' . $this->matches . ' ' . $path);
        }

        if ($this->matches > 0) {
            if (count($results) == 0) {
                throw new \Exception('Not found');
            } else {
                foreach ($results as $result) {
                    if ( !empty( $fullPath ) )
                        $output->writeln($result);
                    else
                        $output->writeln(basename($result));
                }
            }
        }
    }

    protected function walkScope(Scope $scope, $url, $documentRoot)
    {
        $this->url = $url;
        $this->documentRoot = $documentRoot;
        foreach ($scope->getDirectives() as $directive) {
            $this->walkDirective($directive);
        }

        if ($documentRoot && $this->documentRoot){
            foreach ($scope->getDirectives() as $directive) {
                $this->searchInInclude($directive,$documentRoot);
            }
        }
    }

    protected function walkDirective(Directive $directive)
    {
        if ($directive->getChildScope() instanceof Scope) {
            foreach ($directive->getChildScope()->getDirectives() as $directive) {
                $this->walkDirective($directive);
            }
        } else {
            if ($this->url && strpos((string)$directive, 'server_name') !== false) {
                if (strpos((string)$directive, $this->url) !== false) {
                    $this->found++;
                    $this->url = null;
                }
            }
            if ($this->documentRoot && strpos((string)$directive, 'root') === 0) {
                if (strpos((string)$directive, $this->documentRoot) !== false) {
                    $this->found++;
                    $this->documentRoot = null;
                }
            }
        }
    }

    protected function searchInInclude(Directive $directive, $documentRoot)
    {
        if ($directive->getChildScope() instanceof Scope) {
            foreach ($directive->getChildScope()->getDirectives() as $directive) {
                $this->searchInInclude($directive, $documentRoot);
            }
        } else {
            if ( strpos((string)$directive, 'include') !== false) {
                if (strpos((string)$directive, 'nginx') !== false) {
                    $path = trim(str_replace('include', '', $directive));
                    $path = trim(str_replace(';', '', $path));
                    $scope = Scope::fromFile($path);
                    $this->walkScope($scope, null, $this->documentRoot);
                }
            }
        }
    }
}
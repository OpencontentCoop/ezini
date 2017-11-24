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
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Question\ChoiceQuestion;

class InstanceCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    protected $commands = array(
        'create',
        'read',
        'search',
        'run'
    );

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->filesystem = new Filesystem();
    }

    protected function configure()
    {
        $this
            ->setName('instance')
            ->setDescription('Handle instances.yml file')
            ->addArgument(
                'instance-command', InputArgument::REQUIRED,
                'Which command you want to run? choose between ' . implode(', ', $this->commands)
            )
            ->addOption(
                'identifier', 'i',
                InputOption::VALUE_REQUIRED, 'Instance selector'
            )
            ->addOption(
                'file', 'f',
                InputOption::VALUE_OPTIONAL, 'Instance file name', 'instances.yml'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $selected = explode(',', $input->getOption('identifier'));
        $selected = array_map('trim',$selected);
        $file = $input->getOption('file');

        if (!$this->filesystem->exists($file)) {
            throw new \RuntimeException("File $file not found");
        }

        $instances = array();
        $instanceContent = (array)Yaml::parse(file_get_contents($file));
        if (isset( $instanceContent['instances'] )) {
            $instances = $instanceContent['instances'];
        }

        $command = $input->getArgument('instance-command');
        $this->runCommand($command, $instances, $selected);
    }

    protected function runCommand($command, $instances, $selected)
    {
        switch ($command) {
            case 'create':
                $this->createInstance($instances);
                break;

            case 'read':
                $this->readInstance($instances, $selected);
                break;

            case 'search':
                $this->findInstance($instances, $selected);
                break;

            case 'run':
                $this->runOnInstance($instances, $selected);
                break;

            default:
                throw new InvalidArgumentException("Command $command not found");
        }
    }

    protected function createInstance($instances)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select your favorite colors (defaults to red and blue)',
            array('red', 'blue', 'yellow'),
            '0,1'
        );
        $question->setMultiselect(true);
        $question->setMaxAttempts(2);
        $colors = $helper->ask($this->input, $this->output, $question);
        $this->output->writeln('You have just selected: ' . implode(', ', $colors));
    }

    protected function findInstance($instances, $selected)
    {
        $matches = array();
        foreach (array_keys($instances) as $identifier) {
            foreach($selected as $match) {
                if (strpos($identifier, $match) !== false) {
                    $matches[] = $identifier;
                }
            }
        }
        print_r($matches);
    }

    protected function readInstance($instances, $selected)
    {
        foreach($selected as $instanceIdentifier) {
            if (isset( $instances[$instanceIdentifier] )) {
                print_r($instances[$instanceIdentifier]);
            }
        }
    }

    protected function runOnInstance($instances, $selected)
    {

    }
}

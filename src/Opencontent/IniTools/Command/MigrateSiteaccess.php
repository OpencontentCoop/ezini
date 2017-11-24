<?php

namespace Opencontent\IniTools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Opencontent\IniTools\Tools\IniReader;
use Opencontent\IniTools\Tools\IniWriter;


class MigrateSiteaccess extends Command
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

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->filesystem = new Filesystem();
    }

    protected function configure()
    {
        $this
            ->setName('ini_migrate')
            ->setDescription('Migrate ini variable')
            ->addArgument('instance', InputArgument::REQUIRED, 'Instance');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $instance = $input->getArgument('instance');
        $siteaccessList = $this->findSiteaccessList($instance);
        $this->output->writeln('You have just selected: ' . $instance . "\n");
        foreach($siteaccessList as $siteaccess){
            $this->setActiveAccessExtensions($siteaccess);
            $this->setSiteAccessRules($siteaccess);
            if ($siteaccess == $instance . '_' . 'backend'){
                $this->fixBackendOverride($siteaccess);
                $this->fixDesignSettings($siteaccess);
            }
        }
    }

    private function findSiteaccessList($instance)
    {
        $siteaccessList = array();
        $finder = new Finder();
        $directories = $finder->directories()->in('./settings/siteaccess');
        foreach($directories as $directory){
            if (strpos($directory, $instance . '_') !== false){
                $siteaccessList[] = basename($directory->getRelativePathname());
            }
        }

        return $siteaccessList;
    }

    private function readIni($iniName, $siteaccess)
    {
        $ezRoot = getcwd();
        $writer = new IniWriter($ezRoot, $siteaccess);
        $path = "settings/siteaccess/{$siteaccess}/";
        return $writer->getIni($iniName . '.append', $path);
    }

    private function writeIni($iniFile, $siteaccess, $blockName, $variableName, $variableValue, $append = null, $prepend = null, $override = null)
    {
        $ezRoot = getcwd();
        $writer = new IniWriter($ezRoot, $siteaccess);
        $path = "settings/siteaccess/{$siteaccess}/";
        return $writer->setIni($iniFile, $blockName, $variableName, $variableValue, $append, $prepend, $override);
    }

    private function setSiteAccessRules($siteaccess)
    {
        $this->output->writeln("[$siteaccess] Set SiteAccessRules in site.ini");
        $data = array(
            '',
            'access;enable',
            'moduleall',
            'access;disable',
            'module;ezinfo/about',
            'module;setup/extensions',
            'module;content/tipafriend',
            'module;settings/edit'
        );
        $this->writeIni('site.ini', $siteaccess, 'SiteAccessRules', 'Rules', $data);
    }

    private function setActiveAccessExtensions($siteaccess)
    {
        $data = array(
            'ezflow',
            'ezgmaplocation',
            'ezjscore',
            'ezmultiupload',
            'ezodf',
            'ezoe',
            'ezwt',
            'objectrelationfilter',
            'ocmaintenance',
            'weather',
            'wrap_operator',
            'ocimportalbo',
            'occsvimport',
            'openpa_importers',
            'sqliimport',
            'ocinigui',
            'openpa',
            'ezflowplayer',
            'openpa_designs',
            'ezfind',
            'ocsearchtools',
            'ezflip',
            'occhangeobjectdate',
            'ocmediaplayer',
            'jcremoteid',
            'ggwebservices',
            'batchtool',
            'ocmap',
            'ocmaps',
            'ezprestapiprovider',
            'ocopendata',
            'ocexportas',
            'ocuserprofile',
            'occosmos',
            'ocselfimport',
            'ezchangeclass',
            'ezclasslists',
            'collectexport',
            'opensemantic',
            'ocsensorcivico',
            'eztags',
            'ocextensionsorder',
            'bcgooglesitemaps',
            'ocembed',
            'nxc_captcha',
            'bfsurveyfile',
            'mugosurvey_addons',
            'ocsurvey_userlogin',
            'ezsurvey',
            'ezstarrating',
            'enhancedezbinaryfile',
            'ocrss',
            'ocwhatsapp',
            'ocrecaptcha',
            'ezmbpaex',
            'ocmultibinary'
        );

        $this->output->writeln("[$siteaccess] Fix ActiveAccessExtensions in site.ini");
        $this->writeIni('site.ini', $siteaccess, 'ExtensionSettings', 'ActiveAccessExtensions', $data, null, true);
    }

    private function fixDesignSettings($siteaccess)
    {
        $this->output->writeln("[$siteaccess] Fix DesignSettings in site.ini");
        $this->writeIni('site.ini', $siteaccess, 'DesignSettings', 'AdditionalSiteDesignList', '', null, true);
    }

    private function fixBackendOverride($siteaccess)
    {
        $data = array (
            'edit_frontpage' =>
                array (
                    'Source' => 'content/edit.tpl',
                    'MatchFile' => 'edit/frontpage.tpl',
                    'Subdir' => 'templates',
                    'Match' =>
                        array (
                            'class_identifier' => 'frontpage',
                        ),
                ),
            'embed_image' =>
                array (
                    'Source' => 'content/view/embed.tpl',
                    'MatchFile' => 'embed_image.tpl',
                    'Subdir' => 'templates',
                    'Match' =>
                        array (
                            'class_identifier' => 'image',
                        ),
                ),
            'embed-inline_image' =>
                array (
                    'Source' => 'content/view/embed-inline.tpl',
                    'MatchFile' => 'embed-inline_image.tpl',
                    'Subdir' => 'templates',
                    'Match' =>
                        array (
                            'class_identifier' => 'image',
                        ),
                ),
            'embed_node_image' =>
                array (
                    'Source' => 'node/view/embed.tpl',
                    'MatchFile' => 'embed_image.tpl',
                    'Subdir' => 'templates',
                    'Match' =>
                        array (
                            'class_identifier' => 'image',
                        ),
                ),
            'embed-inline_node_image' =>
                array (
                    'Source' => 'node/view/embed-inline.tpl',
                    'MatchFile' => 'embed-inline_image.tpl',
                    'Subdir' => 'templates',
                    'Match' =>
                        array (
                            'class_identifier' => 'image',
                        ),
                ),
            'thumbnail_image_browse' =>
                array (
                    'Source' => 'node/view/browse_thumbnail.tpl',
                    'MatchFile' => 'thumbnail/image_browse.tpl',
                    'Subdir' => 'templates',
                    'Match' =>
                        array (
                            'class_identifier' => 'image',
                        ),
                ),
            'thumbnail_banner' =>
                array (
                    'Source' => 'node/view/thumbnail.tpl',
                    'MatchFile' => 'thumbnail/image.tpl',
                    'Subdir' => 'templates',
                    'Match' =>
                        array (
                            'class_identifier' => 'banner',
                        ),
                ),
            'thumbnail_banner_browse' =>
                array (
                    'Source' => 'node/view/browse_thumbnail.tpl',
                    'MatchFile' => 'thumbnail/image_browse.tpl',
                    'Subdir' => 'templates',
                    'Match' =>
                        array (
                            'class_identifier' => 'banner',
                        ),
                ),
            'tiny_image' =>
                array (
                    'Source' => 'content/view/tiny.tpl',
                    'MatchFile' => 'tiny_image.tpl',
                    'Subdir' => 'templates',
                    'Match' =>
                        array (
                            'class_identifier' => 'image',
                        ),
                ),
        );
        $this->output->writeln("[$siteaccess] Check override.ini");
        $ini = $this->readIni('override.ini', $siteaccess);
        $groups = $ini->groups();
        foreach($data as $group => $value){
            if (!isset($groups[$group])){
                $this->output->writeln("[$siteaccess] Missing block $group in override.ini");
                foreach($value as $block => $variable){
                    $this->writeIni('override.ini', $siteaccess, $group, $block, $variable);
                }
            }
        }
    }
}

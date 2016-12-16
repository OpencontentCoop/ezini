<?php

namespace Opencontent\IniTools\Tools;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class IniReader
{
    protected $previousRunningDir;

    protected $legacyRootDir;

    protected $siteaccessName;

    protected $iniFile;

    public function __construct($legacyRootDir, $siteaccessName = null)
    {
        $this->legacyRootDir = $legacyRootDir;
        $this->siteaccessName = $siteaccessName;
    }

    /**
     * @param string $iniFile
     *
     * @return \eZINI
     */
    public function getIni($iniFile = 'site.ini', $rootDir = 'settings')
    {
        $this->iniFile = $this->getLegacyKernel(function () use ($iniFile, $rootDir) {
            return \eZINI::instance($iniFile, $rootDir, true, false);
        });

        return $this->iniFile;
    }

    protected function getLegacyKernel(\Closure $callback)
    {
        $this->previousRunningDir = getcwd();
        chdir($this->legacyRootDir);

        $fs = new Filesystem();
        if (!$fs->exists('autoload.php')) {
            throw new FileNotFoundException("Legacy root dir not found");
        }

        require 'autoload.php';

        $settings = array();
        if ($this->siteaccessName) {

            if (!$fs->exists("settings/siteaccess/{$this->siteaccessName}")) {
                throw new FileNotFoundException("Siteaccess {$this->siteaccessName} not found");
            }

            $access = array(
                'name' => $this->siteaccessName,
                'type' => \eZSiteAccess::TYPE_STATIC,
                'uri_part' => array()
            );
            $settings = array('siteaccess' => $access);
        }
        $kernel = new \ezpKernel(new \ezpKernelWeb($settings));

        if ($this->siteaccessName) {
            \eZSiteAccess::change($access);
        }

        $return = $kernel->runCallback($callback, true);

        $previousDir = $this->previousRunningDir;
        $this->previousRunningDir = null;
        chdir($previousDir);

        return $return;
    }
}

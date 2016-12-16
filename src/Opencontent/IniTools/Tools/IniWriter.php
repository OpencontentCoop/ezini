<?php

namespace Opencontent\IniTools\Tools;


class IniWriter extends IniReader
{
    public function setIni($iniFile, $blockName, $variableName, $variableValue, $append = null, $prepend = null, $override = null)
    {
        $variableValue = $this->parseValue($variableValue);
        if ($this->siteaccessName) {
            $path = "settings/siteaccess/{$this->siteaccessName}/";
        } elseif ($override) {
            $path = "settings/override/";
        } else {
            throw new \RuntimeException('"--siteaccess" or "--override" option are required in write mode');
        }

        $ini = $this->getIni($iniFile . '.append', $path);
        if ($append || $prepend) {
            $currentValue = $ini->variable($blockName, $variableName);
            if (is_array($currentValue)) {
                if (!is_array($variableValue)) {
                    $variableValue = array( $variableValue );
                }
                if ($prepend) {
                    $makeOverride = false;
                    if ( $currentValue[0] == '' ) {
                        $makeOverride = true;
                        array_shift($currentValue);
                    }
                    $variableValue = array_merge($variableValue, $currentValue);
                    if ( $makeOverride ){
                        array_unshift( $variableValue, '' );
                    }
                }else
                    $variableValue = array_merge($currentValue, $variableValue);

                $variableValue = array_unique( $variableValue );
            }
        }
        $ini->setVariable($blockName, $variableName, $variableValue);
        $ini->save();

        return $variableValue;
    }

    /**
     * @param string $iniFile
     *
     * @return \eZINI
     */
    public function getIni($iniFile = 'site.ini', $rootDir = 'settings')
    {
        $this->iniFile = $this->getLegacyKernel(function () use ($iniFile, $rootDir) {
            return \eZINI::instance($iniFile, $rootDir, null, false, false, true, true);
        });

        return $this->iniFile;
    }

    protected function parseValue($variableValue)
    {
        if (strpos($variableValue, '[') === 0) {
            $variableValue = str_replace('[', '', $variableValue);
            $variableValue = str_replace(']', '', $variableValue);
            $variableValue = explode(',', $variableValue);
            $variableValue = array_map('trim', $variableValue);
            foreach ($variableValue as $value) {
                if (strpos($value, '=>') !== false) {
                    return $this->parseHash($variableValue);
                }
            }
        }

        return $variableValue;
    }

    protected function parseHash($array)
    {
        $variableValue = array();
        foreach ($array as $item) {
            list( $key, $value ) = explode('=>', $item);
            $variableValue[trim($key)] = trim($value);
        }

        return $variableValue;
    }

}

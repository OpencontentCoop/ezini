<?php

namespace Opencontent\IniTools\Tools;


class ParsedIni
{
    protected $ini;
    protected $settings = array();
    protected $blockCount = 0;
    protected $totalSettingCount = 0;
    protected $blocks;

    public function __construct(\eZINI $ini)
    {
        $this->ini = $ini;
        $this->blocks = $ini->groups();
        $placements = $ini->groupPlacements();
        $settings = array();
        $blockCount = 0;
        $totalSettingCount = 0;

        foreach ($this->blocks as $block => $key) {
            $settingsCount = 0;
            $blockRemoveable = false;
            $blockEditable = true;
            foreach ($key as $setting => $settingKey) {
                $hasSetPlacement = false;
                $type = $ini->settingType($settingKey);
                $removeable = false;

                switch ($type) {
                    case 'array':
                        if (count($settingKey) == 0) {
                            $settings[$block]['content'][$setting]['content'] = array();
                        }

                        foreach ($settingKey as $settingElementKey => $settingElementValue) {
                            $settingPlacement = $ini->findSettingPlacement($placements[$block][$setting][$settingElementKey]);
                            if ($settingElementValue != null) {
                                // Make a space after the ';' to make it possible for
                                // the browser to break long lines
                                $settings[$block]['content'][$setting]['content'][$settingElementKey]['content'] = str_replace(';',
                                    "; ", $settingElementValue);
                            } else {
                                $settings[$block]['content'][$setting]['content'][$settingElementKey]['content'] = "";
                            }
                            $settings[$block]['content'][$setting]['content'][$settingElementKey]['placement'] = $settingPlacement;
                            $hasSetPlacement = true;
                            if ($settingPlacement != 'default') {
                                $removeable = true;
                                $blockRemoveable = true;
                            }
                        }
                        break;
                    case 'string':
                        if (strpos($settingKey, ';')) {
                            // Make a space after the ';' to make it possible for
                            // the browser to break long lines
                            $settingArray = str_replace(';', "; ", $settingKey);
                            $settings[$block]['content'][$setting]['content'] = $settingArray;
                        } else {
                            $settings[$block]['content'][$setting]['content'] = $settingKey;
                        }
                        break;
                    default:
                        $settings[$block]['content'][$setting]['content'] = $settingKey;
                }
                $settings[$block]['content'][$setting]['type'] = $type;
                $settings[$block]['content'][$setting]['placement'] = "";

                if (!$hasSetPlacement) {
                    $placement = $ini->findSettingPlacement($placements[$block][$setting]);
                    $settings[$block]['content'][$setting]['placement'] = $placement;
                    if ($placement != 'default') {
                        $removeable = true;
                        $blockRemoveable = true;
                    }
                }
                $editable = $ini->isSettingReadOnly($settingFile, $block, $setting);
                $removeable = $editable === false ? false : $removeable;
                $settings[$block]['content'][$setting]['editable'] = $editable;
                $settings[$block]['content'][$setting]['removeable'] = $removeable;
                ++$settingsCount;
            }
            $blockEditable = $ini->isSettingReadOnly($settingFile, $block);
            $settings[$block]['count'] = $settingsCount;
            $settings[$block]['removeable'] = $blockRemoveable;
            $settings[$block]['editable'] = $blockEditable;
            $totalSettingCount += $settingsCount;
            ++$blockCount;
        }
        ksort($settings);

        $this->settings = $settings;
        $this->totalSettingCount = $totalSettingCount;
        $this->blockCount = $blockCount;
    }

    public function variable($group, $variable)
    {
        return $this->group($group)->find($variable);
    }

    public function group($group)
    {
        $group = isset( $this->settings[$group] ) ? $this->settings[$group] : array();

        return new ParsedIniValue($group);
    }
}
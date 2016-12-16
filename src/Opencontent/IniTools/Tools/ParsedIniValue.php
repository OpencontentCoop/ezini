<?php

namespace Opencontent\IniTools\Tools;

use Symfony\Component\Console\Output\OutputInterface;

class ParsedIniValue
{
    protected $data;

    protected $content;
    protected $count;
    protected $removeable;

    protected $editable;
    protected $type;

    protected $isGroup = true;

    public function __construct(array $data = array())
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->data = $data;

        if ($this->type !== null) {
            $this->isGroup = false;
        }
    }

    public function find($variable)
    {
        $data = array();
        if ($this->isGroup) {
            foreach ($this->content as $key => $value) {
                if ($key == $variable) {
                    $data = $value;
                }
            }
        }

        return new ParsedIniValue($data);
    }

    public function keys()
    {
        if ($this->isGroup || $this->type == 'array') {
            $keys = array('name', 'content', 'type', 'placement', 'editable', 'removeable');
        }else{
            $keys = array_keys($this->data);
        }
        return $keys;
    }

    public function contents()
    {
        //print_r($this->data);die();
        $contents = array();
        if ($this->isGroup) {
            foreach ($this->content as $key => $value) {
                if ($value['type'] == 'array')
                    $this->content[$key]['content'] = '(array)';
                $contents[] = array_merge(array($key), array_values($this->content[$key]));
            }
        } elseif ($this->type == 'array') {
            foreach ($this->content as $key => $value) {
                $contents[] = array(
                    $key,
                    $value['content'],
                    'array_item',
                    $value['placement'],
                    $this->editable,
                    $this->removeable
                );
            }
        } else {
            $contents[] = array_values($this->data);
        }

        return $contents;
    }

}
<?php
namespace Abstraction\Core;

trait TObject
{
    public function convertModelProperties()
    {
        $public = new \stdClass();
        foreach ($this as $key => $value) {
            if (isset($this->mapToModel[$key])) {
                $public->{$this->mapToModel[$key]} = $value;
            }
        }

        return $public;
    }

    public function getMapToModel()
    {
        return $this->mapToModel;
    }

    public function setMapToModel($arr)
    {
        $this->mapToModel = $arr;
    }
}

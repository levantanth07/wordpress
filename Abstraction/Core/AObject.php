<?php
namespace Abstraction\Core;

abstract class AObject
{
    /** @var array $mapToModel map field with model */
    protected $mapToModel;

    /**
     * Call to relative function to update properties
     *
     * @param $attrHash
     */
    public function updateAttributes($attrHash)
    {
        foreach ($attrHash as $attr => $val)
        {
            $action = "set" . ucfirst($attr);
            if (is_callable(array($this, $action))) {
                $this->$action($val);
            }
        }
    }

    /**
     * @return array
     */
    public function getMapToModel()
    {
        return $this->mapToModel;
    }

    /**
     * @param array $mapToModel
     */
    public function setMapToModel($mapToModel)
    {
        $this->mapToModel = $mapToModel;
    } // update attributes

    /**
     * AObject constructor.
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        if(is_array($params)) {
            $this->updateAttributes($params);
        }
    } // end construct

} // end class

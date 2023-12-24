<?php
namespace Abstraction\Core;

/**
 * Class AResultObject
 *
 * @package SSAbstraction\Core
 */
abstract class AResult {
    /** @var string message */
    public $message;

    /** @var int code */
    public $messageCode;

    /** @var int number of results */
    public $numberOfResult;

    /** @var int total of query */
    public $total;

    /** @var int last page of query */
    public $lastPage;

    /** @var int current page of query */
    public $currentPage;

    /** @var mixed result content everything of return data, eg: array, object.... */
    public $result;

    public function convertResultToResourceObject()
    {
        if (is_array($this->result)) {
            $temp = [];
            foreach ($this->result as $one) {
                $resource = $one->convertToResourceObject();
                $temp[] = $resource;
            }
            return $temp;
        } else {
            return $this->result->convertToResourceObject();
        }
    } // end convert result to resource object

    /**
     * @return string $this->result as json
     */
    public function convertResultToJson()
    {
        return json_encode($this->result);
    }

    /**
     * Print out whole of this object as a json file
     */
    public function returnResultObjectToJson()
    {
        header('Content-Type: application/json');
        echo json_encode($this);
    }

    /**
     * Print out whole of this object as json file
     * ON FAILURE
     */
    public function returnResultObjectToJsonOnFailure()
    {
        $this->message = "Failure";
        $this->messageCode = 0;
        header('Content-Type: application/json');
        echo json_encode($this);
    }
}

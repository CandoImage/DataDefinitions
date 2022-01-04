<?php

namespace Wvision\Bundle\DataDefinitionsBundle\Fetcher;

use Exception;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Concrete;

class MockupObject extends Concrete
{
    /**
     * @var int
     */
    protected int $id;

    /**
     * @var array
     */
    protected array $params;

    /**
     * @var string
     */
    protected string $tableName;


    public function __construct($id, $params)
    {
        $this->id = $id;
        $this->params = $params;

        parent::__construct();
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getParam(string $key)
    {
        return $this->params[$key];
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function __call($method, $args)
    {
        $attributeName = $method;
        if (substr($method, 0, 3) == 'get') {
            $attributeName = lcfirst(substr($method, 3));
        }

        if (is_array($this->params) && array_key_exists($attributeName, $this->params)) {
            return $this->params[$attributeName];
        }

        $msg = "Method $method not in Mockup implemented, delegating to object with id {$this->id}.";

        if (\Pimcore::inDebugMode()) {
            Logger::warn($msg);
        } else {
            Logger::info($msg);
        }

        $object = $this->getOriginalObject();
        if ($object) {
            if (method_exists($object, $method)) {
                return call_user_func_array([$object, $method], $args);
            }

            $method = 'get' . ucfirst($method);
            if (method_exists($object, $method)) {
                return call_user_func_array([$object, $method], $args);
            }
        }

        throw new Exception("Object with {$this->id} not found.");
    }

    /**
     * @throws Exception
     */
    public function getOriginalObject()
    {
        throw new Exception('not supported');
    }
}

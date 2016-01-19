<?php


namespace Silktide\LazyBoy\Test\Mocks;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class MockApplication extends Application
{
    protected static $calls = [];
    protected static $returns = [];

    public function __construct()
    {
        // Very important that it does NOT allow the parent constructor to run
    }

    static function reset()
    {
        self::$calls = [];
        self::$returns = [];
    }

    // Parental overrides

    public function offsetGet($id)
    {
        return $this->addFunctionCall("offsetGet", func_get_args());
    }

    public function offsetSet($offset, $value)
    {
        return $this->addFunctionCall("offsetSet", func_get_args());
    }

    public function register(\Pimple\ServiceProviderInterface $provider, array $values = [])
    {
        return $this->addFunctionCall("register", func_get_args());
    }

    public function run(Request $request=null)
    {
        return $this->addFunctionCall("run", func_get_args());
    }

    public function __call($name, $arguments)
    {
        return $this->addFunctionCall($name, $arguments);
    }

    public function addFunctionCall($name, $arguments)
    {
        if (!isset(self::$calls[$name])) {
            self::$calls[$name] = [];
        }

        self::$calls[$name][] = $arguments;

        if (isset(self::$returns[$name])) {
            return self::$returns[$name];
        }
    }

    // Actual functions that we'll call from outside the class

    public function setReturn($functionName, $returnValue)
    {
        self::$returns[$functionName] = $returnValue;
    }

    public function getCalledResponse($functionName)
    {
        if (!isset(self::$calls[$functionName])) {
            throw new \Exception("No function by this name was called `{$functionName}`");
        }

        if (count(self::$calls[$functionName]) == 0) {
            throw new \Exception("Function call stack is empty `{$functionName}`");
        }

        return array_shift(self::$calls[$functionName]);
    }
}


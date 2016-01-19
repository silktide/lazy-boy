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

    }

    static function reset()
    {
        self::$calls = [];
        self::$returns = [];
    }

    /*public function __call($arg1, $arg2)
    {

    }*/
    public function offsetGet($id)
    {
        $value = $this->addFunctionCall("offsetGet", func_get_args());
        if ($value) {
            return $value;
        } else {
            return parent::offsetGet($id);
        }
    }

    public function offsetSet($offset, $value)
    {
        $this->addFunctionCall("offsetSet", func_get_args());
    }

    public function register(\Pimple\ServiceProviderInterface $provider, array $values = [])
    {
        //$this->__call("register", func_get_args());
        $this->addFunctionCall("register", func_get_args());
    }


    public function __call($name, $arguments)
    {
        $this->addFunctionCall($name, $arguments);
    }

    public function addFunctionCall($name, $arguments)
    {
        if (!isset(self::$calls[$name])) {
            self::$calls[$name] = [];
        }

        self::$calls[$name][] = $arguments;

        if (isset(self::$returns[$name])) {
            //foreach (self::$returns[$name] as $return) {
                //if ($return[0]==$arguments) {
                //    return $return[1];
                //}
                // return $return == $arguments;
            //}
            return self::$returns[$name];
        }
    }

/*    public function setReturn($functionName, $args=[], $returnValue)
    {
        if (!isset(self::$returns[$functionName])) {
            self::$returns[$functionName] = [];
        }

        self::$returns[$functionName][] = [$args, $returnValue];
    }
*/
    public function setReturn($functionName, $returnValue)
    {
        self::$returns[$functionName] = $returnValue;
    }


    public function run(Request $request=null)
    {
        // STFU
        $this->addFunctionCall("run", func_get_args());
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

    public function getCalled($functionName, $args, $times=1)
    {
        if (!isset(self::$calls[$functionName])) {
            return false;
        }

        $run = 0;
        foreach (self::$calls[$functionName] as $call) {
            if ($call == $args) {
                $run++;
            }
        }

        //var_dump([$run, $times]);

        //var_dump([$run, $times]);
        //die();

        return ($run == $times);
    }
    /*public function register($providerInterface)
    {
        self::$calls[] = ["register", func_get_args()];
    }

    public function offsetGet($id)
    {
        self::$calls[] = ["offsetGet", func_get_args()];
    }*/

    /*public function fuckyou()
    {


        $mockApplication = new MockApplication();
        $mockApplication->setReturn("offsetGet", $this->routeLoader);
            //$mockedApplication = \Mockery::mock("Silex\\Application")->shouldIgnoreMissing();
            //$mockedClassName = get_class($mockedApplication);
            //MockApplication::mockReset();
            //MockApplication::setOffsetSet("routeLoader", $this->routeLoader);
            //$this->application->shouldReceive("register")->with($this->serviceProvider)->times(3);
            //$this->application->shouldReceive("offsetGet")->with("routeLoader")->once()->andReturn($this->routeLoader);

        $controller = new FrontController($this->builder, "configDir", get_class($mockApplication), $providers);
        $controller->runApplication();

            //$mockApplication->getCalls();
        $this->assertTrue($mockApplication->received("register", [$this->serviceProvider], 3));
        $this->assertTrue($mockApplication->received("offsetGet", [$this->routeLoader]));
}*/
}


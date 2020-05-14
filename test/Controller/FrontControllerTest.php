<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\LazyBoy\Test;

use PHPUnit\Framework\TestCase;
use Silktide\LazyBoy\Controller\FrontController;
use Silktide\LazyBoy\Test\Mocks\MockApplication;
use Silktide\Syringe\ContainerBuilder;
use Silktide\LazyBoy\Config\RouteLoader;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;

/**
 *
 */
class FrontControllerTest extends TestCase
{

    /**
     * @var \Mockery\Mock|ContainerBuilder
     */
    protected $builder;

    /**
     * @var \Mockery\Mock|RouteLoader
     */
    protected $routeLoader;

    /**
     * @var \Mockery\Mock|Application
     */
    protected $application;

    /**
     * @var \Mockery\Mock|ServiceControllerServiceProvider
     */
    protected $serviceProvider;


    public function setUp() : void
    {
        $this->builder = \Mockery::mock("Silktide\\Syringe\\ContainerBuilder")->shouldIgnoreMissing();
        $this->routeLoader = \Mockery::mock("Silktide\\LazyBoy\\RouteLoader")->shouldIgnoreMissing();
        $this->application = \Mockery::mock("Silex\\Application")->shouldIgnoreMissing();
        $this->serviceProvider = \Mockery::mock("Pimple\\ServiceProviderInterface")->shouldIgnoreMissing();

    }

    public function testSettingApplicationClass()
    {
        // default class
        $class = FrontController::DEFAULT_APPLICATION_CLASS;
        $controller = new FrontController($this->builder, "", $class);
        $this->assertAttributeEquals($class, "applicationClass", $controller);

        // subclass
        $class = get_class($this->application);
        $controller = new FrontController($this->builder, "", $class);
        $this->assertAttributeEquals($class, "applicationClass", $controller);

        // invalid class
        try {
            $controller = new FrontController($this->builder, "", __CLASS__);
            $this->fail("Should not be able to create a FrontController with an invalid application class");
        } catch (\InvalidArgumentException $e) {
        }

    }

    public function testApplicationRun()
    {
        MockApplication::reset();
        $mockApplication = new MockApplication();
        $mockAppClass = get_class($mockApplication);
        $configDir = "configDir";

        $mockApplication->setReturn("offsetGet", $this->routeLoader);
        $this->routeLoader->shouldReceive("parseRoutes")->with("/^$configDir/")->once();

        $controller = new FrontController($this->builder, $configDir, $mockAppClass);
        $controller->runApplication();

        $this->assertEquals("app", $mockApplication->getCalledResponse("offsetSet")[0]);
        $this->assertEquals(["routeLoader"], $mockApplication->getCalledResponse("offsetGet"));
        $this->assertEquals([], $mockApplication->getCalledResponse("run"));
    }

    public function testSettingProviders()
    {
        $providers = [
            $this->serviceProvider,
            $this->serviceProvider,
            $this->serviceProvider,
        ];

        MockApplication::reset();
        $mockApplication = new MockApplication();
        $mockApplication->setReturn("offsetGet", $this->routeLoader);
        $controller = new FrontController($this->builder, "configDir", get_class($mockApplication), $providers);
        $controller->runApplication();

        $this->assertEquals(["routeLoader"], $mockApplication->getCalledResponse("offsetGet"));
        $this->assertEquals([$this->serviceProvider], $mockApplication->getCalledResponse("register"));
        $this->assertEquals([$this->serviceProvider], $mockApplication->getCalledResponse("register"));
        $this->assertEquals([$this->serviceProvider], $mockApplication->getCalledResponse("register"));


    }

    public function tearDown() : void
    {
        \Mockery::close();
    }

}
 
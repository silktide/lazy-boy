<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\LazyBoy\Controller;

use Silktide\Syringe\ContainerBuilder;
use Silex\Application;
use Silktide\LazyBoy\Config\RouteLoader;
use Silex\Provider\ServiceControllerServiceProvider;

/**
 * FrontController - loads routes, builds and runs the application
 */
class FrontController 
{

    /**
     * @var ContainerBuilder
     */
    protected $builder;

    /**
     * @var string
     */
    protected $configDir;

    /**
     * @param ContainerBuilder $builder
     * @param string $configDir
     */
    public function __construct(ContainerBuilder $builder, $configDir)
    {
        $this->builder = $builder;
        $this->configDir = $configDir;
    }

    public function runApplication()
    {
        // create application
        $this->builder->setContainerClass("Silex\\Application");
        /** @var Application $application */
        $application = $this->builder->createContainer();
        $application["app"] = function() use ($application) {
            return $application;
        };

        // register service controller provider
        $application->register(new ServiceControllerServiceProvider());

        // load routes
        /** @var RouteLoader $routeLoader */
        $routeLoader = $application["routeLoader"];
        $routeLoader->parseRoutes($this->configDir . "/routes.json");

        // run the app
        $application->run();
    }

} 
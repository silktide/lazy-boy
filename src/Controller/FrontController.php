<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\LazyBoy\Controller;

use Pimple\ServiceProviderInterface;
use Silktide\Syringe\ContainerBuilder;
use Silex\Application;
use Silktide\LazyBoy\Config\RouteLoader;

/**
 * FrontController - loads routes, builds and runs the application
 */
class FrontController 
{

    const DEFAULT_APPLICATION_CLASS = "Silex\\Application";

    /**
     * @var ContainerBuilder
     */
    protected $builder;

    /**
     * @var string
     */
    protected $configDir;

    /**
     * @var string
     */
    protected $applicationClass;

    /**
     * @var array
     */
    protected $serviceProviders;

    /**
     * @param ContainerBuilder $builder
     * @param string $configDir
     * @param string $applicationClass
     * @param array $serviceProviders
     */
    public function __construct(ContainerBuilder $builder, $configDir, $applicationClass, array $serviceProviders = [])
    {
        $this->builder = $builder;
        $this->configDir = $configDir;
        $this->setApplicationClass($applicationClass);
        $this->setProviders($serviceProviders);
    }

    protected function setApplicationClass($applicationClass) {
        if ($applicationClass != self::DEFAULT_APPLICATION_CLASS && !is_subclass_of($applicationClass, self::DEFAULT_APPLICATION_CLASS)) {
            throw new \InvalidArgumentException(sprintf("The class '%s' is not a subclass of '%s'", $applicationClass, self::DEFAULT_APPLICATION_CLASS));
        }
        $this->applicationClass = $applicationClass;
    }

    protected function setProviders(array $providers)
    {
        $this->serviceProviders = [];
        foreach ($providers as $provider) {
            if ($provider instanceof ServiceProviderInterface) {
                $this->serviceProviders[] = $provider;
            }
        }
    }

    public function runApplication()
    {
        // create application
        $this->builder->setContainerClass($this->applicationClass);
        /** @var Application $application */
        $application = $this->builder->createContainer();
        $application["app"] = function() use ($application) {
            return $application;
        };

        // register service controller provider
        foreach ($this->serviceProviders as $provider) {
            $application->register($provider);
        }

        // load routes
        /** @var RouteLoader $routeLoader */
        $routeLoader = $application["routeLoader"];
        $routeLoader->parseRoutes($this->configDir . "/routes.json");

        // run the app
        $application->run();
    }

} 
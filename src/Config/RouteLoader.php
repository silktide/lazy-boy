<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\LazyBoy\Config;

use Silex\Application;
use Silktide\LazyBoy\Exception\RouteException;
use Silktide\Syringe\Exception\LoaderException;
use Silktide\Syringe\Loader\LoaderInterface;

/**
 * Load routes into the application
 */
class RouteLoader 
{

    /**
     * @var array
     */
    protected $allowedMethods = [
        "get" => true,
        "post" => true,
        "put" => true,
        "delete" => true,
        "patch" => true
    ];

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var LoaderInterface[]
     */
    protected $loaders = [];

    /**
     * @param Application $application
     * @param array $loaders
     */
    public function __construct(Application $application, array $loaders=[])
    {
        $this->application = $application;

        foreach ($loaders as $loader) {
            if ($loader instanceof LoaderInterface) {
                $this->addLoader($loader);
            }
        }
    }

    /**
     * @param LoaderInterface $loader
     */
    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * @param array|string $routes - route data or filePath to route data
     * @throws RouteException
     * @throws \InvalidArgumentException
     */
    public function parseRoutes($routes)
    {
        // if we don't have a data array, see if we can load it from a file
        if (!is_array($routes)) {
            if (!is_string($routes)) {
                throw new \InvalidArgumentException("The \$routes argument must be an array or a filePath");
            }
            // check $routes is a filePath
            if (!file_exists($routes)) {
                throw new RouteException("Cannot load routes, the file '$routes' does not exist");
            }

            try{
                $loader = $this->selectLoader($routes);
                $routes = $loader->loadFile($routes);
            } catch(LoaderException $e) {
                throw new RouteException($e->getMessage());
            }
        }

        // validation
        if (empty($routes["routes"])) {
            throw new RouteException("The routes data array does not contain a 'routes' element at it's base");
        }
        if (!is_array($routes["routes"])) {
            throw new RouteException("The routes data array is not in the correct format");
        }

        foreach ($routes["routes"] as $routeName => $config) {
            // route validation
            if (empty($config["url"]) || empty($config["action"])) {
                throw new RouteException("The data for the '$routeName' route is missing required elements");
            }
            if (empty($config["method"])) {
                $config["method"] = "get";
            } else {
                $config["method"] = strtolower($config["method"]);
                // check method is allowed
                if (!isset($this->allowedMethods[$config["method"]])) {
                    throw new RouteException("The method '{$config["method"]}' for route '$routeName' is not allowed");
                }
            }
            // add the route
            $this->application->{$config["method"]}($config["url"], $config["action"]);
        }
    }


    /**
     * @param $file
     * @return LoaderInterface
     * @throws \Exception||LoaderException
     */
    protected function selectLoader($file)
    {
        foreach ($this->loaders as $loader) {
            /** @var LoaderInterface $loader */
            if ($loader->supports($file)) {
                return $loader;
            }
        }
        throw new LoaderException(sprintf("The file '%s' is not supported by any of the available loaders", $file));
    }
} 
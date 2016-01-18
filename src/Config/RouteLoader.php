<?php

namespace Silktide\LazyBoy\Config;

use Silex\Application;
use Silktide\LazyBoy\Exception\RouteException;
use Silktide\LazyBoy\Security\SecurityContainer;
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
     * @var SecurityContainer
     */
    protected $securityContainer = [];

    /**
     * @param Application $application
     * @param SecurityContainer $securityContainer
     * @param array $loaders
     */
    public function __construct(Application $application, SecurityContainer $securityContainer, array $loaders=[])
    {
        $this->application = $application;
        $this->securityContainer = $securityContainer;

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
     * @param string $baseUrl - used to track the base URL within a group of routes. This argument should only be required for internal recursion
     * @throws RouteException
     */
    public function parseRoutes($routes, $baseUrl = "")
    {
        // if we don't have a data array, see if we can load it from a file
        if (!is_array($routes)) {
            $routes = $this->loadFile($routes);
        }

        $hasProcessed = false;
        // process groups
        if (!empty($routes["groups"])) {
            if (!is_array($routes["groups"])) {
                throw new RouteException("The group data array for '$baseUrl' is not in the correct format");
            }

            foreach ($routes["groups"] as $groupName => $config) {
                if (empty($config["urlPrefix"])) {
                    throw new RouteException("The group '$groupName' does not have a URL associated with it");
                }
                $this->parseRoutes($config, $baseUrl . $config["urlPrefix"]);
                $hasProcessed = true;
            }

        }

        // validation
        if (!empty($routes["routes"])) {
            if (!is_array($routes["routes"])) {
                throw new RouteException("The routes data array for '$baseUrl' is not in the correct format");
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
                $this->application->{$config["method"]}($baseUrl . $config["url"], $config["action"])->bind($routeName);

                // apply security if required
                $security = null;
                if (isset($config["public"])) {
                    $security = ["public" => $config["public"]];
                } elseif (!empty($config["security"])) {
                    $security = $config["security"];
                }
                if ($security !== null) {
                    $this->securityContainer->setSecurityForRoute($routeName, $security);
                }
                $hasProcessed = true;
            }
        }

        if (!$hasProcessed) {
            throw new RouteException("The routes configuration was empty. No routes or groups were defined for '$baseUrl'");
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

    protected function loadFile($routes)
    {
        if (!is_string($routes)) {
            throw new \InvalidArgumentException("The \$routes argument must be an array or a filePath");
        }

        $filePath = $routes;
        // check $routes is a filePath
        if (!file_exists($filePath)) {
            throw new RouteException("Cannot load routes, the file '$filePath' does not exist");
        }

        try{
            $loader = $this->selectLoader($filePath);
            $routes = $loader->loadFile($filePath);
        } catch(LoaderException $e) {
            throw new RouteException($e->getMessage());
        }
        // load any route files we've been asked to import
        if (!empty($routes["imports"])) {
            // the import file will be relative to this file, so get the file's directory
            $rootPath = substr($filePath, 0, strrpos($filePath, "/") + 1);
            foreach ($routes["imports"] as $import) {
                // load the import and merge with the route file
                $importedRoutes = $this->loadFile($rootPath . $import);

                $routes = array_replace_recursive($importedRoutes, $routes);
            }
        }
        return $routes;
    }

} 
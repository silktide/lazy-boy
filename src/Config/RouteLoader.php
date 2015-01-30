<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\LazyBoy\Config;

use Silex\Application;
use Silktide\LazyBoy\Exception\RouteException;

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
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
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
            // get the data (should be in JSON format)
            $data = json_decode(file_get_contents($routes), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RouteException("The file '$routes' could not be parsed as JSON data: " . json_last_error_msg());
            }
            $routes = $data;
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

} 
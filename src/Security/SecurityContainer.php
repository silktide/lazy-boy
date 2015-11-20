<?php

namespace Silktide\LazyBoy\Security;

/**
 * SecurityContainer
 */
class SecurityContainer
{

    protected $routes = [];

    protected $defaultSecurity = [];

    public function __construct($defaultSecurity = [])
    {
        $this->defaultSecurity = $defaultSecurity;
    }

    public function setSecurityForRoute($route, $security)
    {
        $this->routes[$route] = $security;
    }

    public function getSecurityForRoute($route)
    {
        return isset($this->routes[$route])
            ? $this->routes[$route]
            : $this->defaultSecurity;
    }

}
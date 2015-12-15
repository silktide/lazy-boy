<?php

namespace Silktide\LazyBoy\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enables application to handles CORS requests
 *
 * TODO: Review security implications
 */
class CorsServiceProvider implements ServiceProviderInterface
{

    public function register(Container $app) {
        if ($app instanceof Application) {
            //handling CORS preflight request
            $app->before(function (Request $request) {
                if ($request->getMethod() === "OPTIONS") {
                    $response = new Response();
                    $response->headers->set("Access-Control-Allow-Origin","*");
                    $response->headers->set("Access-Control-Allow-Methods","GET,POST,PUT,DELETE,OPTIONS");
                    $response->headers->set("Access-Control-Allow-Headers","Content-Type,Authorization");
                    $response->setStatusCode(200);
                    $response->send();
                    exit();
                }
            }, Application::EARLY_EVENT);

            //handling CORS response with right headers
            $app->after(function (Request $request, Response $response) {
                $response->headers->set("Access-Control-Allow-Origin","*");
                $response->headers->set("Access-Control-Allow-Methods","GET,POST,PUT,DELETE,OPTIONS");
            });
        }
    }

} 
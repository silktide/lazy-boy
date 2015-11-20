<?php

namespace Silktide\LazyBoy\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts a request body to parameters if it is in JSON format
 */
class JsonPostServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app) {
        if ($app instanceof Application) {

            $app->before(function (Request $request) {
                // check if the content type is JSON
                if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                    // decode and check for errors
                    $data = json_decode($request->getContent(), true);
                    if (empty($data) && json_last_error() != JSON_ERROR_NONE) {
                        return new Response('{"error": "The request body was not in JSON format"}');
                    }
                    // modify the request with the decoded data
                    $request->request->add(is_array($data) ? $data : []);
                }
            }, Application::EARLY_EVENT + 1); // set priority to be almost always first to run
        }
    }
} 
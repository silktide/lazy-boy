<?php

namespace Silktide\LazyBoy\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Automatically decodes JSON request content and adds the values to the Request::request property
 */
class JsonPostServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $container)
    {
        // nothing to do here
    }
    
    public function boot(Application $app)
    {
        //accepting JSON
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
            return null;
        }, Application::EARLY_EVENT + 1); // set priority to be almost always first to run
    }
} 
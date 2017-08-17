<?php

namespace Silktide\LazyBoy\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonErrorProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $container)
    {
        // nothing to do here
    }

    public function boot(Application $app)
    {
        $app->error(function (\Exception $e, Request $request, $code) use ($app) {
            switch ($code) {
                case 404:
                    $title = 'Sorry, the page you are looking for could not be found.';
                    break;
                default:
                    $title = 'Whoops, looks like something went wrong.';
            }

            $response = [
                "message" => $title
            ];

            if ($app["debug"]) {
                $response["error"] = $e->getMessage();
                $response["stackTrace"] = $e->getTrace();
            }

            return new JsonResponse($response, $code);
        });
    }

} 
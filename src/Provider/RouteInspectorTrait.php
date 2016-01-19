<?php

namespace Silktide\LazyBoy\Provider;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcher;

/**
 * RouteInspectorTrait
 */
trait RouteInspectorTrait
{

    protected function getRoute(Application $app, Request $request)
    {
        $context = $app["request_context"];
        $context->fromRequest($request);

        /** @var UrlMatcher $matcher */
        $matcher = $app["request_matcher"];
        $matcher->setContext($context);
        return $matcher->matchRequest($request);
    }

}
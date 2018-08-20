<?php


namespace Silktide\LazyBoy\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use \Silktide\Syringe\Syringe;

class SyringeServiceProvider implements ServiceProviderInterface
{
    protected $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function register(Container $container)
    {
        $config = $this->config;
        $config["containerService"] = $container;
        return Syringe::build($config);
    }
}

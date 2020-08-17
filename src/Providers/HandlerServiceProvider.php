<?php

declare(strict_types=1);

namespace Datashaman\Phial\Providers;

use Circli\EventDispatcher\EventDispatcher;
use Circli\EventDispatcher\ListenerProvider\ContainerListenerProvider;
use Datashaman\Phial\Config;
use Datashaman\Phial\ConfigInterface;
use Datashaman\Phial\ContextFactory;
use Datashaman\Phial\ContextFactoryInterface;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class HandlerServiceProvider implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [
            ConfigInterface::class => function (ContainerInterface $container) {
                $config = new Config();

                $files = glob(base_dir('config/*.php'));

                if ($files !== false) {
                    foreach ($files as $filename) {
                        $name = basename($filename, '.php');
                        $config->set($name, require($filename));
                    }
                }

                $env = getenv('APP_ENV');

                if ($env !== false && file_exists(base_dir("config/env/$env.php"))) {
                    $config->mergeRecursiveDistinct(require(base_dir("config/env/$env.php")));
                }

                return $config;
            },
            ContextFactoryInterface::class => fn(ContainerInterface $container) =>
                $container->get(ContextFactory::class),
            EventDispatcherInterface::class => fn(ContainerInterface $container) =>
                $container->get(EventDispatcher::class),
            ListenerProviderInterface::class => fn(ContainerInterface $container) =>
                $container->get(ContainerListenerProvider::class),
        ];
    }

    public function getExtensions()
    {
        return [];
    }
}

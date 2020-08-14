<?php

declare(strict_types=1);

namespace Datashaman\Phial\Providers;

use Circli\EventDispatcher\EventDispatcher;
use Circli\EventDispatcher\ListenerProvider\ContainerListenerProvider;
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

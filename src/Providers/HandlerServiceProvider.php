<?php

declare(strict_types=1);

namespace Datashaman\Phial\Providers;

use Datashaman\Phial\Lambda\ContextFactory;
use Datashaman\Phial\Lambda\ContextFactoryInterface;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

class HandlerServiceProvider implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [
            ContextFactoryInterface::class => fn(ContainerInterface $container) =>
                $container->get(ContextFactory::class),
        ];
    }

    public function getExtensions()
    {
        return [];
    }
}

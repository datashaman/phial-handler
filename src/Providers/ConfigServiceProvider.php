<?php

declare(strict_types=1);

namespace Datashaman\Phial\Providers;

use Datashaman\Phial\Config;
use Datashaman\Phial\ConfigInterface;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

class ConfigServiceProvider implements ServiceProviderInterface
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
        ];
    }

    public function getExtensions()
    {
        return [];
    }
}

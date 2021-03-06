<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters(
        [
            'auto_import_names' => true,
        ]
    );

    $parameters->set('paths', [__DIR__ . '/src']);

    $parameters->set('sets', ['php74', 'phpstan']);
};

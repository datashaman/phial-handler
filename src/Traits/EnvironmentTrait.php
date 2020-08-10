<?php

declare(strict_types=1);

namespace Datashaman\Phial\Traits;

use Exception;

trait EnvironmentTrait
{
    private function getEnv(string $varname): string
    {
        $value = getenv($varname);

        if ($value !== false) {
            return $value;
        }

        throw new Exception('Environment variable not found: ' . $varname);
    }
}

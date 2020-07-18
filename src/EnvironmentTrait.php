<?php

declare(strict_types=1);

namespace Datashaman\Phial;

use Exception;

trait EnvironmentTrait
{
    private function getEnv(string $varname): string
    {
        if ($value = getenv($varname)) {
            return $value;
        }

        throw new Exception('Environment variable not found: ' . $varname);
    }
}

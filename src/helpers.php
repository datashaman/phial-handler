<?php

declare(strict_types=1);

if (!function_exists('base_dir')) {
    function base_dir(string $path = ''): string
    {
        if ($path && $path[0] === '/') {
            $path = substr($path, 1);
        }

        if (defined('BASE_DIR')) {
            return BASE_DIR . ($path ? "/$path" : '');
        }

        throw new Exception('BASE_DIR is not defined');
    }
}

if (!function_exists('env')) {
    /**
     * @param ?mixed $default
     *
     * @return ?mixed
     */
    function env(string $name, $default = null)
    {
        $value = getenv($name);

        return $value === false ? $default : $value;
    }
}

if (!function_exists('is_true')) {
    /**
     * @param mixed $value
     */
    function is_true($value): bool
    {
        $value = is_string($value)
            ? mb_strtolower($value)
            : $value;

        $checks = [
            null,
            false,
            'false',
            'off',
            'no',
            '0',
            '',
            0,
            0.0,
        ];

        return !in_array(
            $value,
            $checks,
            true
        );
    }
}

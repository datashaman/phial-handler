<?php

declare(strict_types=1);

namespace Datashaman\Phial;

/**
 * Interface for Dot class, which does not include one.
 *
 * https://github.com/adbario/php-dot-notation
 */
interface DotInterface
{
    /**
     * Set a given key / value pair or pairs
     * if the key doesn't exist already
     *
     * @param array<int|string>|int|string $keys
     * @param mixed            $value
     *
     * @return void
     */
    public function add($keys, $value = null);

    /**
     * Return all the stored items
     *
     * @return array<int|string,mixed>
     */
    public function all();

    /**
     * Delete the contents of a given key or keys
     *
     * @param array<int|string>|int|string|null $keys
     *
     * @return void
     */
    public function clear($keys = null);

    /**
     * Delete the given key or keys
     *
     * @param array<int|string>|int|string $keys
     *
     * @return void
     */
    public function delete($keys);

    /**
     * Flatten an array with the given character as a key delimiter
     *
     * @param  string     $delimiter
     * @param  array<int|string,mixed>|null $items
     * @param  string     $prepend
     *
     * @return array<int|string,mixed>
     */
    public function flatten($delimiter = '.', $items = null, $prepend = '');

    /**
     * Return the value of a given key
     *
     * @param  int|string|null $key
     * @param  mixed           $default
     *
     * @return mixed
     */
    public function get($key = null, $default = null);

    /**
     * Check if a given key or keys exists
     *
     * @param  array<int,string>|int|string $keys
     *
     * @return bool
     */
    public function has($keys);

    /**
     * Check if a given key or keys are empty
     *
     * @param  array<int,string>|int|string|null $keys
     *
     * @return bool
     */
    public function isEmpty($keys = null);

    /**
     * Merge a given array or a Dot object with the given key
     * or with the whole Dot object
     *
     * @param array<int|string,mixed>|string|self $key
     * @param array<int|string,mixed>|self        $value
     *
     * @return void
     */
    public function merge($key, $value = []);

    /**
     * Recursively merge a given array or a Dot object with the given key
     * or with the whole Dot object.
     *
     * Duplicate keys are converted to arrays.
     *
     * @param array<int|string,mixed>|string|self $key
     * @param array<int|string,mixed>|self        $value
     *
     * @return void
     */
    public function mergeRecursive($key, $value = []);

    /**
     * Recursively merge a given array or a Dot object with the given key
     * or with the whole Dot object.
     *
     * Instead of converting duplicate keys to arrays, the value from
     * given array will replace the value in Dot object.
     *
     * @param array<int|string,mixed>|string|self $key
     * @param array<int|string,mixed>|self        $value
     *
     * @return void
     */
    public function mergeRecursiveDistinct($key, $value = []);

    /**
     * Return the value of a given key and
     * delete the key
     *
     * @param  int|string|null $key
     * @param  mixed           $default
     *
     * @return mixed
     */
    public function pull($key = null, $default = null);

    /**
     * Push a given value to the end of the array
     * in a given key
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function push($key, $value = null);

    /**
     * Replace all values or values within the given key
     * with an array or Dot object
     *
     * @param array<int|string>|string|self $key
     * @param array<int|string,mixed>|self        $value
     *
     * @return void
     */
    public function replace($key, $value = []);

    /**
     * Set a given key / value pair or pairs
     *
     * @param array<int|string>|int|string $keys
     * @param mixed            $value
     *
     * @return void
     */
    public function set($keys, $value = null);

    /**
     * Replace all items with a given array
     *
     * @param mixed $items
     *
     * @return void
     */
    public function setArray($items);

    /**
     * Replace all items with a given array as a reference
     *
     * @param array<int|string,mixed> $items
     *
     * @return void
     */
    public function setReference(array &$items);

    /**
     * Return the value of a given key or all the values as JSON
     *
     * @param  mixed  $key
     * @param  int    $options
     *
     * @return string
     */
    public function toJson($key = null, $options = 0);
}

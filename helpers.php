<?php

use Winter\Storm\Support\Facades\Config;

if (!function_exists('media_url')) {
    /**
     * @deprecated since 2.4.0; use Winter's storage URL facilities directly
     */
    function media_url($path = '')
    {
        return url(Config::get('cms.storage.media.path') . '/' . $path);
    }
}

if (!function_exists('plugins_url')) {
    /**
     * @deprecated since 2.4.0; use Winter's plugin path and URL facilities directly
     */
    function plugins_url($path = '')
    {
        return url(Config::get('cms.pluginsPath') . '/' . $path);
    }
}

if (!function_exists('array_search_value')) {
    /**
     * @deprecated since 2.4.0; use native collection or array operations
     */
    function array_search_value(array $array, string $search, string $key)
    {
        $_key = array_search($search, array_column($array, $key));

        if ($_key === false) {
            return null;
        }

        return ['__key' => $_key] + $array[$_key];
    }
}

if (!function_exists('array_sort_value')) {
    /**
     * @deprecated since 2.4.0; use native collection or array operations
     */
    function array_sort_value(array $array, string $key)
    {
        usort($array, function ($a, $b) use ($key) {
            return $a[$key] <=> $b[$key];
        });

        return $array;
    }
}

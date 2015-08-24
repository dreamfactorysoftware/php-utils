<?php namespace DreamFactory\Library\Utility;

/**
 * Contains helpers to include/require PHP files
 */
class Includer
{
    /**
     * Requires a file only if it exists
     *
     * @param string $file the absolute /path/to/file.php
     * @param bool   $require use "require" instead of "include"
     * @param bool   $once use "include_once" or "require_once" if $require is true
     *
     * @return bool|mixed
     */
    public static function includeIfExists($file, $require = false, $once = false)
    {
        if (file_exists($file) && is_readable($file)) {
            /** @noinspection PhpIncludeInspection */
            return $require
                ? ($once ? require_once($file) : require($file)) : ($once ? include_once($file)
                    : include($file));
        }

        return false;
    }
}
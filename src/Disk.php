<?php namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Utility\Enums\GlobFlags;
use DreamFactory\Library\Utility\Exceptions\FileSystemException;

/**
 * Down and dirty file utility class with a sprinkle of awesomeness
 */
class Disk
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Builds a path from arguments and validates existence.
     *
     *      $_path = Disk::path(['path','to','my','stuff'], true);
     *
     *      The result is "/path/to/my/stuff"
     *
     * @param array|string $parts     The segments of the path to build
     * @param bool         $create    If true, and result path doesn't exist, it will be created
     * @param int          $mode      mkdir: octal mode for creating new directories
     * @param bool         $recursive mkdir: recursively create directory
     *
     * @return string Returns the path
     */
    public static function path($parts, $create = false, $mode = 0777, $recursive = true)
    {
        $_path = static::segment($parts, true);

        if ($_path && DIRECTORY_SEPARATOR != $_path[0]) {
            logger('Build path without leading slash: ' . $_path);
        }

        if (empty($_path)) {
            if ($create) {
                throw new FileSystemException('Path "' . $_path . '" cannot be created.');
            }

            return null;
        }

        if ($create && !static::ensurePath($_path, $mode, $recursive)) {
            throw new FileSystemException('Unable to create directory "' . $_path . '".');
        }

        return $_path;
    }

    /**
     * Returns suitable appendage based on segment.
     *
     * @param array|string|null $segment   path segment
     * @param bool              $leading   If true, leading $separator ensured, otherwise stripped
     * @param string            $separator The directory separator to use
     *
     * @return null|string Returns the concatenated string with or without $leading slash. If an empty
     * $separator is the result, null will be returned.
     */
    public static function segment($segment = null, $leading = false, $separator = DIRECTORY_SEPARATOR)
    {
        return Scalar::concat($segment, $leading, $separator);
    }

    /**
     * As found on php.net posted by: BigueNique at yahoo dot ca 20-Apr-2010 07:15
     * A safe empowered glob().
     *
     * Supported flags: GLOB_MARK, GLOB_NOSORT, GLOB_ONLYDIR
     * Additional flags: GlobFlags::GLOB_NODIR, GlobFlags::GLOB_PATH, GlobFlags::GLOB_NODOTS, GlobFlags::GLOB_RECURSE
     * (not original glob() flags, defined here)
     *
     * @author BigueNique AT yahoo DOT ca
     *
     * @param string $pattern
     * @param int    $flags
     *
     * @return array|bool
     */
    public static function glob($pattern, $flags = 0)
    {
        $pattern = static::normalizePath($pattern);

        $_split = explode(DIRECTORY_SEPARATOR,
            str_replace('\\', DIRECTORY_SEPARATOR, ltrim($pattern, DIRECTORY_SEPARATOR)));

        $_mask = array_pop($_split);
        $_leading = (DIRECTORY_SEPARATOR == $pattern[0]);
        $_path = ($_leading ? DIRECTORY_SEPARATOR : null) . implode(DIRECTORY_SEPARATOR, $_split);

        $_glob = false;

        if (false !== ($_directory = opendir($_path))) {
            $_glob = [];

            while (false !== ($_file = readdir($_directory))) {
                $_fullPath = $_path . DIRECTORY_SEPARATOR . $_file;

                //	Recurse directories
                if (is_dir($_fullPath) && ($flags & GlobFlags::GLOB_RECURSE) && in_array($_file, ['.', '..'])) {
                    $_glob = array_merge($_glob,
                        Scalar::array_prepend(static::glob($_fullPath . DIRECTORY_SEPARATOR . $_mask, $flags),
                            ($flags & GlobFlags::GLOB_PATH ? null : $_file . DIRECTORY_SEPARATOR)));
                }

                // Match file mask
                if (fnmatch($_mask, $_file)) {
                    if (((!($flags & GLOB_ONLYDIR)) || is_dir($_fullPath)) &&
                        ((!($flags & GlobFlags::GLOB_NODIR)) || (!is_dir($_fullPath))) &&
                        ((!($flags & GlobFlags::GLOB_NODOTS)) || (!in_array($_file,
                                ['.', '..'])))
                    ) {
                        $_glob[] =
                            ($flags & GlobFlags::GLOB_PATH ? $_path . '/' : null) .
                            $_file .
                            ($flags & GLOB_MARK ? '/' : '');
                    }
                }
            }

            closedir($_directory);

            if (!empty($_glob) && !($flags & GLOB_NOSORT)) {
                sort($_glob);
            }
        }

        return $_glob;
    }

    /**
     * rmdir function with force
     *
     * @param string $dirPath
     * @param bool   $force If true, non-empty directories will be deleted
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function rmdir($dirPath, $force = false)
    {
        $_path = rtrim($dirPath) . DIRECTORY_SEPARATOR;

        if (!$force) {
            return rmdir($_path);
        }

        return static::deleteTree($dirPath);
    }

    /**
     * Fixes up bogus paths that start out Windows then go linux (i.e. C:\MyDSP\public/storage/.private/scripts)
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalizePath($path)
    {
        if ('\\' == DIRECTORY_SEPARATOR) {
            if (isset($path, $path[1], $path[2]) && ':' === $path[1] && '\\' === $path[2]) {
                if (false !== strpos($path, '/')) {
                    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
                }
            }
        }

        return $path;
    }

    /**
     * Ensures that a path exists
     * If path does not exist, it is created. If creation fails, FALSE is returned.
     *
     * NOTE: Output of mkdir is squelched.
     *
     * @param string $path      The path the ensure
     * @param int    $mode      The mode
     * @param bool   $recursive If true recursive creation
     *
     * @return bool FALSE if the directory does not exist nor can be created
     */
    public static function ensurePath($path, $mode = 0777, $recursive = true)
    {
        try {
            if (!is_dir($path) && !@mkdir($path, $mode, $recursive)) {
                throw new FileSystemException('mkdir() failed');
            }
        } catch (\Exception $_ex) {
            //  can't write or make directory?
            /** @noinspection PhpUndefinedMethodInspection */
            //Log::error('[Disk::ensurePath] error ensuring "' . $path . '": ' . $_ex->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Deletes an entire directory tree
     *
     * @param string $path
     *
     * @return bool
     */
    public static function deleteTree($path)
    {
        if (file_exists($path) && !is_dir($path)) {
            throw new \InvalidArgumentException('The $path "' . $path . '" is not a directory.');
        }

        if (!is_dir($path)) {
            return true;
        }

        foreach (array_diff(scandir($path), ['.', '..']) as $_file) {
            $_entry = $path . DIRECTORY_SEPARATOR . $_file;
            (is_dir($_entry) && !is_link($path)) ? static::deleteTree($_entry) : unlink($_entry);
        }

        return rmdir($path);
    }
}

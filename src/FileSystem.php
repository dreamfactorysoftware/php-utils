<?php namespace DreamFactory\Library\Utility;

/**
 * Down and dirty file utility class with a sprinkle of awesomeness
 *
 * @deprecated Please use Utility\Disk instead.
 */
class FileSystem
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * As found on php.net posted by: BigueNique at yahoo dot ca 20-Apr-2010 07:15
     * A safe empowered glob().
     *
     * Supported flags: GLOB_MARK, GLOB_NOSORT, GLOB_ONLYDIR
     * Additional flags: GlobFlags::GLOB_NODIR, GlobFlags::GLOB_PATH, GlobFlags::GLOB_NODOTS, GlobFlags::GLOB_RECURSE
     * (not original glob() flags, defined here)
     *
     * @author     BigueNique AT yahoo DOT ca
     *
     * @param string $pattern
     * @param int    $flags
     *
     * @return array|bool
     * @deprecated Use Disk::glob() instead.
     */
    public static function glob($pattern, $flags = 0)
    {
        return Disk::glob($pattern, $flags);
    }

    /**
     * rmdir function with force
     *
     * @param string $dirPath
     * @param bool   $force If true, non-empty directories will be deleted
     *
     * @return bool
     * @throws \InvalidArgumentException
     * @deprecated Use Disk::rmdir() instead.
     */
    public static function rmdir($dirPath, $force = false)
    {
        return Disk::rmdir($dirPath, $force);
    }

    /**
     * Fixes up bogus paths that start out Windows then go linux (i.e. C:\MyDSP\public/storage/.private/scripts)
     *
     * @param string $path
     *
     * @return string
     * @deprecated Use Disk::normalizePath() instead.
     */
    public static function normalizePath($path)
    {
        return Disk::normalizePath($path);
    }

    /**
     * Ensures that a path exists
     * If path does not exist, it is created. If creation fails, FALSE is returned.
     *
     * NOTE: Output of mkdir is squelched.
     *
     * @param string $path
     *
     * @return bool FALSE if the directory does not exist nor can be created
     * @deprecated Use Disk::ensurePath() instead.
     */
    public static function ensurePath($path)
    {
        return Disk::ensurePath($path);
    }

    /**
     * Builds a path from arguments and validates existence.
     *
     *      $_path = FileSystem::buildPath(['path','to','my','stuff'], true);
     *
     *      The result is "/path/to/my/stuff"
     *
     * @param array $parts  The segments of the path to build
     * @param bool  $create If true, and result path doesn't exist, it will be created
     * @param int   $mode   The octal mode for creating new directories
     *
     * @return string Returns the created path
     * @deprecated Use Disk::path() instead.
     */
    public static function buildPath(array $parts, $create = true, $mode = 0777)
    {
        return Disk::path($parts, $create, $mode, true);
    }

    /**
     * Builds a path from arguments and validates existence.
     *
     *      $_path = FileSystem::makePath(true,'path','to','my','stuff')
     *
     *      The result is "/path/to/my/stuff"
     *
     * @param bool         $createMissing If true,  and result path doesn't exist, it will be created
     * @param string|array $segments      One or more directory parts to assemble
     *
     * @return bool|string Returns the created path or false if non-existent
     *
     * @deprecated Use static::buildPath() or Disk::path() instead.
     */
    public static function makePath($createMissing = true, $segments = null)
    {
        $_parts = func_get_args();
        array_shift($_parts);

        return Disk::path($_parts, $createMissing);
    }
}

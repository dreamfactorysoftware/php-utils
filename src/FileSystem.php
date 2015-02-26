<?php
namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Utility\Enums\GlobFlags;
use DreamFactory\Library\Utility\Exceptions\FileSystemException;

/**
 * Down and dirty file utility class with a sprinkle of awesomeness
 */
class FileSystem
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Builds a path from arguments and validates existence.
     *
     *      $_path = FileSystem::makePath(true,'path','to','my','stuff')
     *
     *      The result is "/path/to/my/stuff"
     *
     * @param bool   $createMissing If true,  and result path doesn't exist, it will be created
     * @param string $part          One or more directory parts to assemble
     *
     * @return bool|string Returns the created path or false if non-existent
     * @deprecated because it sucks. Bool or string? WTF. Use static::buildPath() instead.
     */
    public static function makePath( $createMissing = true, $part = null )
    {
        $_path = null;

        $_parts = func_get_args();
        array_shift( $_parts );

        foreach ( $_parts as $_part )
        {
            !empty( $_part ) && $_path .= DIRECTORY_SEPARATOR . trim( $_part, DIRECTORY_SEPARATOR . ' ' );
        }

        if ( !$createMissing )
        {
            return is_dir( $_path ) ? $_path : false;
        }

        static::ensurePath( $_path );

        return $_path;
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
    public static function glob( $pattern, $flags = 0 )
    {
        $pattern = static::normalizePath( $pattern );

        $_split = explode(
            DIRECTORY_SEPARATOR,
            str_replace( '\\', DIRECTORY_SEPARATOR, ltrim( $pattern, DIRECTORY_SEPARATOR ) )
        );

        $_mask = array_pop( $_split );
        $_leading = ( DIRECTORY_SEPARATOR == $pattern[0] );
        $_path = ( $_leading ? DIRECTORY_SEPARATOR : null ) . implode( DIRECTORY_SEPARATOR, $_split );

        $_glob = false;

        if ( false !== ( $_directory = opendir( $_path ) ) )
        {
            $_glob = array();

            while ( false !== ( $_file = readdir( $_directory ) ) )
            {
                $_fullPath = $_path . DIRECTORY_SEPARATOR . $_file;

                //	Recurse directories
                if ( is_dir( $_fullPath ) && ( $flags & GlobFlags::GLOB_RECURSE ) && in_array( $_file, array('.', '..') ) )
                {
                    $_glob = array_merge(
                        $_glob,
                        Scalar::array_prepend(
                            static::glob( $_fullPath . DIRECTORY_SEPARATOR . $_mask, $flags ),
                            ( $flags & GlobFlags::GLOB_PATH ? null : $_file . DIRECTORY_SEPARATOR )
                        )
                    );
                }

                // Match file mask
                if ( fnmatch( $_mask, $_file ) )
                {
                    if ( ( ( !( $flags & GLOB_ONLYDIR ) ) || is_dir( $_fullPath ) ) &&
                        ( ( !( $flags & GlobFlags::GLOB_NODIR ) ) || ( !is_dir( $_fullPath ) ) ) &&
                        ( ( !( $flags & GlobFlags::GLOB_NODOTS ) ) || ( !in_array( $_file, array('.', '..') ) ) )
                    )
                    {
                        $_glob[] = ( $flags & GlobFlags::GLOB_PATH ? $_path . '/' : null ) . $_file . ( $flags & GLOB_MARK ? '/' : '' );
                    }
                }
            }

            closedir( $_directory );

            if ( !empty( $_glob ) && !( $flags & GLOB_NOSORT ) )
            {
                sort( $_glob );
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
    public static function rmdir( $dirPath, $force = false )
    {
        $_path = rtrim( $dirPath ) . DIRECTORY_SEPARATOR;

        if ( !$force )
        {
            return rmdir( $_path );
        }

        if ( !is_dir( $_path ) )
        {
            throw new \InvalidArgumentException( '"' . $_path . '" is not a directory or bogus in some other way.' );
        }

        $_files = glob( $_path . '*', GLOB_MARK );

        foreach ( $_files as $_file )
        {
            if ( is_dir( $_file ) )
            {
                static::rmdir( $_file, true );
            }
            else
            {
                unlink( $_file );
            }
        }

        return rmdir( $_path );
    }

    /**
     * Fixes up bogus paths that start out Windows then go linux (i.e. C:\MyDSP\public/storage/.private/scripts)
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalizePath( $path )
    {
        if ( '\\' == DIRECTORY_SEPARATOR )
        {
            if ( isset( $path, $path[1], $path[2] ) && ':' === $path[1] && '\\' === $path[2] )
            {
                if ( false !== strpos( $path, '/' ) )
                {
                    $path = str_replace( '/', DIRECTORY_SEPARATOR, $path );
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
     * @param string $path
     *
     * @return bool FALSE if the directory does not exist nor can be created
     */
    public static function ensurePath( $path )
    {
        if ( !is_dir( $path ) && !@mkdir( $path, 0777, true ) )
        {
            return false;
        }

        @chmod( $path, 02775 );

        return true;
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
     */
    public static function buildPath( array $parts, $create = true, $mode = 0777 )
    {
        $_path = null;

        foreach ( $parts as $_part )
        {
            !empty( $_part ) && $_path .= DIRECTORY_SEPARATOR . trim( $_part, DIRECTORY_SEPARATOR . ' ' );
        }

        $_path = realpath( $_path );

        if ( $create && !static::ensurePath( $_path, $mode ) )
        {
            throw new FileSystemException( 'Error while creating directory "' . $_path . '".' );
        }

        return $_path;
    }
}

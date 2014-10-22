<?php
namespace DreamFactory\Library\Utility;

use Kisma\Core\Exceptions\FileSystemException;

/**
 * Contains helpers that discover information about the current runtime environment
 */
class Environment
{
    /**
     * @type string
     */
    const ROOT_MARKER = '/.dreamfactory.php';
    /**
     * @type string
     */
    const FABRIC_MARKER = '/var/www/.fabric_hosted';
    /**
     * @type string
     */
    const MAINTENANCE_MARKER = '/var/www/.fabric_maintenance';
    /**
     * @type string
     */
    const PRIVATE_PATH = '/storage/.private';

    /**
     * Try a variety of cross platform methods to determine the current user
     *
     * @return bool|string
     */
    public static function getUserName()
    {
        //  List of places to get users in order
        $_users = array(
            getenv( 'USER' ),
            isset( $_SERVER, $_SERVER['USER'] ) ? $_SERVER['USER'] : false,
            get_current_user()
        );

        foreach ( $_users as $_user )
        {
            if ( !empty( $_user ) )
            {
                return $_user;
            }
        }

        throw new \LogicException( 'Cannot determine current user name.' );
    }

    /**
     * Determine the host name of this machine. First HTTP_HOST is used from PHP $_SERVER if available. Otherwise the
     * PHP gethostname() call is used.
     *
     * @param bool $checkServer If false, the $_SERVER variable is not checked.
     *
     * @return string
     */
    public static function getHostname( $checkServer = true )
    {
        //	Figure out my name
        if ( $checkServer && isset( $_SERVER, $_SERVER['HTTP_HOST'] ) )
        {
            $_parts = explode( '.', $_SERVER['HTTP_HOST'] );

            if ( count( $_parts ) > 0 )
            {
                return $_parts[0];
            }
        }

        return gethostname();
    }

    /**
     * Gets a temporary path suitable for writing by the current user...
     *
     * @param string $subPath      The sub-directory of the temporary path created that is required
     * @param bool   $ensureExists If true, and the directory does not exist, it will be created
     *
     * @return bool|string
     */
    public static function getTempPath( $subPath = null, $ensureExists = true )
    {
        $_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $subPath;

        if ( !is_dir( $_path ) )
        {
            if ( $ensureExists && false !== mkdir( $_path, 0777, true ) )
            {
                return $_path;
            }

            return false;
        }

        //  If I can't read/write to path, make a new one for me myself and I
        if ( !is_readable( $_path ) || !is_writeable( $_path ) )
        {
            return static::getTempPath( static::getUserName() . DIRECTORY_SEPARATOR . $subPath, $ensureExists );
        }

        //  Looks good
        return $_path;
    }

    /**
     * Returns a SHA256 hash of a string that can be used as a key for caching
     *
     * @param string|int $entropy   Any additional entropy to add to the concatenation before hashing
     * @param string     $algorithm The algorithm to use when hashing. Defaults to SHA256
     *
     * @return string
     */
    public static function createRequestId( $entropy = null, $algorithm = 'sha256' )
    {
        $_hostname = Environment::getHostname();

        return hash(
            $algorithm,
            PHP_SAPI .
            '_' .
            ( isset( $_SERVER, $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : $_hostname ) .
            '_' .
            $_hostname .
            ( $entropy ? '_' . $entropy : null )
        );
    }

    /**
     * Locates the installed DSP's base directory
     *
     * @param string $startPath
     *
     * @throws FileSystemException
     * @return string|bool The absolute path to the platform installation. False if not found
     */
    public static function locatePlatformBasePath( $startPath = __DIR__ )
    {
        //  Start path given or this file's directory
        $_path = $startPath ?: __DIR__;

        while ( true )
        {
            $_path = rtrim( $_path, ' /' );

            if ( file_exists( $_path . static::ROOT_MARKER ) && is_dir( $_path . static::PRIVATE_PATH ) )
            {
                break;
            }

            //  Too low, go up a level
            $_path = dirname( $_path );

            //	If we get to the root, ain't no DSP...
            if ( '/' == $_path || empty( $_path ) )
            {
                return false;
            }
        }

        return $_path;
    }

}
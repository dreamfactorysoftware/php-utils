<?php
namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Enterprise\Storage\Enums\EnterpriseDefaults;
use DreamFactory\Library\Enterprise\Storage\Enums\EnterprisePaths;
use DreamFactory\Library\Enterprise\Storage\Resolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains helpers that discover information about the current runtime environment
 */
class Environment extends EnterpriseDefaults
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type Request
     */
    protected $_request;
    /**
     * @type Response
     */
    protected $_response;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param Request  $request
     * @param Response $response
     */
    public function __construct( Request $request = null, Response $response = null )
    {
        $this->_request = $request ?: $this->getRequest();
        $this->_response = $response ?: $this->getResponse();
    }

    /**
     * Try a variety of cross platform methods to determine the current user
     *
     * @return bool|string
     */
    public function getUserName()
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
     * @param bool $fqdn        If true, the fully qualified domain name is returned. Otherwise just the first portion.
     *
     * @return string
     */
    public function getHostname( $checkServer = true, $fqdn = true )
    {
        //	Figure out my name
        if ( $checkServer && isset( $_SERVER, $_SERVER['HTTP_HOST'] ) )
        {
            $_hostname = $_SERVER['HTTP_HOST'];
        }
        else
        {
            $_hostname = gethostname();
        }

        $_parts = explode( '.', $_hostname );

        return
            $fqdn
                ? $_hostname
                : ( count( $_parts ) ? $_parts[0] : $_hostname );
    }

    /**
     * Gets a temporary path suitable for writing by the current user...
     *
     * @param string $subPath The sub-directory of the temporary path created that is required
     *
     * @return bool|string
     */
    public function getTempPath( $subPath = null )
    {
        return FileSystem::ensurePath( sys_get_temp_dir() . DIRECTORY_SEPARATOR . ltrim( $subPath, DIRECTORY_SEPARATOR ) );
    }

    /**
     * Returns a SHA256 hash of a string that can be used as a key for caching
     *
     * @param string|int $entropy   Any additional entropy to add to the concatenation before hashing
     * @param string     $algorithm The algorithm to use when hashing. Defaults to SHA256
     *
     * @return string
     */
    public function createRequestId( $entropy = null, $algorithm = 'sha256' )
    {
        $_hostname = static::getHostname();

        return hash(
            $algorithm,
            PHP_SAPI .
            '_' .
            IfSet::get( $_SERVER, 'REMOTE_ADDR', $_hostname ) .
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
     * @return string|bool The absolute path to the platform installation. False if not found
     */
    public function locatePlatformBasePath( $startPath = null )
    {
        //  Start path given or this file's directory
        $_path = $startPath ?: __DIR__;

        while ( true )
        {
            $_path = rtrim( $_path, ' /' );

            //  Vendor and autoload?
            if ( file_exists( $_path . static::COMPOSER_MARKER ) )
            {
                break;
            }

            //  Installation root?
            if ( file_exists( $_path . static::INSTALL_ROOT_MARKER ) &&
                is_dir( $_path . EnterprisePaths::STORAGE_PATH . EnterprisePaths::PRIVATE_STORAGE_PATH )
            )
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

    /**
     * @return bool True if this uses hosted/shared storage
     */
    public function isHosted()
    {
        static $_hostedInstance = null;
        static $_validRoots = array(EnterpriseDefaults::DEFAULT_DOC_ROOT, EnterpriseDefaults::DEFAULT_DEV_DOC_ROOT);

        return
            $_hostedInstance = $_hostedInstance
                ?:
                in_array( IfSet::get( $_SERVER, 'DOCUMENT_ROOT' ), $_validRoots ) &&
                ( file_exists( EnterpriseDefaults::FABRIC_MARKER ) || file_exists( EnterpriseDefaults::ENTERPRISE_MARKER ) );
    }

    /**
     * @param string $zone
     * @param bool   $partitioned
     *
     * @return bool|string
     * @todo convert to resource locator
     */
    public function locateZone( $zone = null, $partitioned = false )
    {
        if ( !empty( $zone ) )
        {
            return $zone;
        }

        //  Zones only apply to partitioned layouts
        if ( !$partitioned )
        {
            return false;
        }

        //  Try ec2...
        $_url = getenv( 'EC2_URL' ) ?: Resolver::DEBUG_ZONE_URL;

        //  Not on EC2, we're something else
        if ( empty( $_url ) )
        {
            return false;
        }

        //  Get the EC2 zone of this instance from the url
        $_zone = str_ireplace( array('https://', '.amazonaws.com'), null, $_url );

        return $_zone;
    }

    /**
     * Given a storage ID, return its partition
     *
     * @param string $storageId
     * @param bool   $partitioned
     *
     * @return string
     * @todo convert to resource locator
     */
    public function locatePartition( $storageId, $partitioned )
    {
        return $partitioned ? substr( $storageId, 0, 2 ) : false;
    }

    /**
     * Locates the installation root of DSP
     *
     * @param string $start
     *
     * @return string
     * @todo convert to resource locator
     */
    public function locateInstallRoot( $start = null )
    {
        $_path = $start ?: getcwd();

        while ( true )
        {
            if ( file_exists( $_path . DIRECTORY_SEPARATOR . 'composer.json' ) && is_dir( $_path . DIRECTORY_SEPARATOR . 'vendor' ) )
            {
                break;
            }

            $_path = dirname( $_path );

            if ( empty( $_path ) || $_path == DIRECTORY_SEPARATOR )
            {
                throw new \RuntimeException( 'Platform installation path not found.' );
            }
        }

        return $_path;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request ?: $this->_request = Request::createFromGlobals();
    }

    /**
     * @param Request $request
     *
     * @return Environment
     */
    public function setRequest( $request )
    {
        $this->_request = $request;

        return $this;
    }

    /**
     * @param mixed $content
     * @param int   $status
     * @param array $headers
     *
     * @return Response
     */
    public function getResponse( $content = null, $status = 200, $headers = array() )
    {
        return $this->_response ?: $this->_response = Response::create( $content, $status, $headers );
    }

    /**
     * @param Response $response
     *
     * @return Environment
     */
    public function setResponse( $response )
    {
        $this->_response = $response;

        return $this;
    }

}
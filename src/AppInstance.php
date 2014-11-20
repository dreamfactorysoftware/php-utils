<?php
namespace DreamFactory\Library\Utility;

use Composer\Autoload\ClassLoader;
use DreamFactory\Library\Enterprise\Storage\Enums\EnterpriseResources;
use DreamFactory\Library\Enterprise\Storage\Resolver;
use Kisma\Core\Components\Flexistore;
use Kisma\Core\Components\PaddedLineFormatter;
use Kisma\Core\Enums\PhpFrameworks;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A generalized abstraction of ContainerBuilder for future customization
 */
class AppInstance extends ContainerBuilder
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string
     */
    const DEFAULT_NAMESPACE = 'dreamfactory';

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type Request
     */
    protected static $_request;
    /**
     * @type Response
     */
    protected static $_response;
    /**
     * @type AppInstance
     */
    protected static $_instance;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param ParameterBagInterface $parameterBag
     * @param ClassLoader           $autoloader
     */
    public function __construct( ParameterBagInterface $parameterBag = null, $autoloader = null )
    {
        $this->parameterBag = $this->parameterBag ?: new ParameterBag();

        static::$_instance = $this;
        static::$_request = Request::createFromGlobals();
        static::$_response = Response::create();

        $parameterBag = new ParameterBag(
            $this->_initializeDefaults( IfSet::get( $_SERVER, 'DOCUMENT_ROOT' ), $parameterBag ? $parameterBag->all() : array() )
        );

        try
        {
            $_resolver = $parameterBag->get( 'resolver' );
            $parameterBag->remove( 'resolver' );
        }
        catch ( ParameterNotFoundException $_ex )
        {
            throw new ParameterNotFoundException( 'The runtime parameter "resolver" has not been set.' );
        }

        $autoloader && $this->set( 'autoloader', $autoloader );
        $_resolver && $this->set( 'resolver', $_resolver );

        parent::__construct( $parameterBag );

        $this->configure();
    }

    /**
     * Initialize the instance based on parameter settings
     */
    public function configure()
    {
        try
        {
            if ( true === $this->getParameter( 'app.debug', static::NULL_ON_INVALID_REFERENCE ) )
            {
                ini_set( 'display_errors', true );
                defined( 'YII_DEBUG' ) or define( 'YII_DEBUG', true );
            }
        }
        catch ( ParameterNotFoundException $_ex )
        {
            //  Ignored
        }

        try
        {
            //  php-error utility
            if ( true === $this->get( 'app.debug.use_php_error', static::NULL_ON_INVALID_REFERENCE ) && function_exists( 'reportErrors' ) )
            {
                reportErrors();
            }
        }
        catch ( ParameterNotFoundException $_ex )
        {
            //  Ignored
        }
    }

    /**
     * Bootstraps the instance with default settings
     *
     * @param string $documentRoot The document root of the server
     *
     * @return $this
     */
    public function run( $documentRoot )
    {
        $_basePath = $this->getParameter( 'app.base_path' );
        $_configPath = $this->getParameter( 'app.config_path' );

        //	Load constants...
        Includer::includeIfExists( $_configPath . DIRECTORY_SEPARATOR . 'constants.config.php', true, false );

        //  Initialize the app store
        static::_createStore( $_basePath );

        //	Create an alias for our configuration directory
        static::alias( 'application.config', $_configPath );
        static::alias( 'application.log', $this->getParameter( 'app.log_path' ) );

        //	Load up any other aliases
        Includer::includeIfExists( $_configPath . '/aliases.config.php', false, true );

        /** Application settings into persistent storage */
        $_hostname = $this->getParameter( 'app.host_name' );

        $_runConfig = array_merge(
            $this->parameterBag->all(),
            array(
                'app.run_id'             => $this->_getAppRunId( $_hostname ),
                'platform.host_name'     => $_hostname,
                'platform.fabric_hosted' => $this->getParameter( 'app.hosted_instance' ),
                'app.storage_path'       => $this->get( 'resolver' )->getStoragePath(),
                'app.private_path'       => $this->get( 'resolver' )->getPrivatePath(),
                'app.config'             => static::_getAppConfig(),
            )
        );

        \Kisma::set( $_runConfig );

        //	Register the autoloader cuz Yii clobbers it somehow
        switch ( $this->getParameter( 'app.framework' ) )
        {
            case PhpFrameworks::Yii:
                \Yii::registerAutoloader( array($this->get( 'autoloader' ), 'loadClass'), $this->getParameter( 'app.append_autoloader' ) );
                break;
        }

        //.........................................................................
        //. App Create & Run...
        //.........................................................................

        //	Instantiate and run baby!
        /** @type \CApplication $_app */
        $_app = \Yii::createApplication( $this->getParameter( 'app.class' ), $_runConfig['app.config'] );
        static::app( $_app );

        $this->getParameter( 'app.auto_run', true ) && $_app->run();

        //	Return our spawn
        return $_app;
    }

    /**
     * @return AppInstance
     */
    public static function getInstance()
    {
        return self::$_instance;
    }

    /**
     * @return Request
     */
    public static function getRequest()
    {
        return self::$_request;
    }

    /**
     * @return Response
     */
    public static function getResponse()
    {
        return self::$_response;
    }

    /**
     * @return array|null
     */
    protected function _getAppConfig()
    {
        $_configFile = $this->getParameter( 'app.config_file' );

        try
        {
            $_config = $this->getParameter( 'app.config' );
        }
        catch ( ParameterNotFoundException $_ex )
        {
            $_config = null;
        }

        //  If not cached, or we still don't have a configuration array, read from the file
        if ( empty( $_config ) && !empty( $_configFile ) )
        {
            /** @noinspection PhpIncludeInspection */
            if ( false === ( $_config = @require( $_configFile ) ) )
            {
                throw new \RuntimeException( 'File system error reading configuration file "' . $_configFile . '"' );
            }
        }

        return $_config;
    }

    //********************************************************************************
    //* Convenience Mappings
    //********************************************************************************

    /**
     * @param \CApplication $app
     *
     * @return \CConsoleApplication|\CWebApplication|\DreamFactory\Platform\Yii\Components\PlatformConsoleApplication|\DreamFactory\Platform\Yii\Components\PlatformWebApplication
     */
    public static function app( $app = null )
    {
        /** @var $_thisApp \CApplication|\CWebApplication|\CConsoleApplication */
        static $_thisApp = null;

        if ( false === $app )
        {
            $_thisApp = null;
        }

        if ( !$_thisApp )
        {
            $_thisApp = $app ?: \Yii::app();

            //	Non-CLI requests have clientScript and a user maybe
            if ( $_thisApp )
            {
                if ( 'cli' == PHP_SAPI )
                {
                    static::$_instance->setParameter( 'yii.client_script', $_thisApp->getComponent( 'clientScript', false ) );
                    static::$_instance->setParameter( 'yii.user', $_thisApp->getComponent( 'user', false ) );
                }

                static::$_instance->setParameter( 'yii.request', $_thisApp->getComponent( 'request', false ) );
                static::$_instance->setParameter( 'yii.params', $_thisApp->getParams() );
            }
        }

        return $_thisApp;
    }

    /**
     * @param string $prefix If specified, only parameters with this prefix will be returned
     * @param bool   $regex  If true, $prefix will be treated as a regex pattern
     *
     * @return array
     */
    public static function params( $prefix = null, $regex = false )
    {
        try
        {
            $_params = static::$_instance->getParameter( 'yii.params' );
        }
        catch ( ParameterNotFoundException $_ex )
        {
            static::$_instance->setParameter( 'yii.params', $_params = static::app()->getParams() );
        }

        if ( empty( $_params ) )
        {
            $_params = array();
        }

        if ( null !== $prefix )
        {
            $_parameters = array();

            if ( false === $regex )
            {
                //	Make sure a trailing dot is added to prefix...
                $prefix = trim( strtolower( rtrim( $prefix, ' .' ) . '.' ) );
            }

            foreach ( $_params as $_key => $_value )
            {
                if ( false !== $regex )
                {
                    if ( 1 != preg_match( $prefix, $_key, $_matches ) )
                    {
                        continue;
                    }

                    $_parameters[str_ireplace( $_matches[0], null, $_key )] = $_value;
                }
                elseif ( false !== stripos( $_key, $prefix, 0 ) )
                {
                    $_parameters[str_ireplace( $prefix, null, $_key )] = $_value;
                }
            }

            return $_parameters;
        }

        return $_params;
    }

    /**
     * Returns application parameters or default value if not found
     *
     * @param string $paramName
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public static function getParam( $paramName, $defaultValue = null )
    {
        $_parameters = static::params();

        return IfSet::get( $_parameters, $paramName, $defaultValue );
    }

    /**
     * Sets an application parameter value for this request.
     * *** DOES NOT PERSIST TO CONFIG FILE ***
     *
     * @param string $paramName
     * @param mixed  $value
     */
    public static function setParam( $paramName, $value = null )
    {
        static::app()->params[$paramName] = $value;
    }

    /**
     * Get or set a path alias. If $path is provided, this acts like a "setter" otherwise a "getter"
     * Note, this method neither checks the existence of the path nor normalizes the path.
     *
     * @param string $alias    alias to the path
     * @param string $path     the path corresponding to the alias. If this is null, the corresponding
     *                         path alias will be removed.
     * @param string $morePath When retrieving an alias, $morePath will be appended to the end
     *
     * @return mixed|null|string
     */
    public static function alias( $alias, $path = null, $morePath = null )
    {
        if ( null !== $path )
        {
            \Yii::setPathOfAlias( $alias, $path );

            return $path;
        }

        $_path = \Yii::getPathOfAlias( $alias );

        if ( null !== $morePath )
        {
            $_path = trim(
                rtrim( $_path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . ltrim( $morePath, DIRECTORY_SEPARATOR )
            );
        }

        return $_path;
    }

    /**
     * @return string
     */
    protected static function _determineHostName()
    {
        static $_hostname = null;

        if ( $_hostname )
        {
            return $_hostname;
        }

        //	Figure out my name
        if ( isset( $_SERVER, $_SERVER['HTTP_HOST'] ) )
        {
            $_parts = explode( '.', $_SERVER['HTTP_HOST'] );

            if ( 4 == count( $_parts ) )
            {
                if ( 'cumulus' == ( $_hostname = $_parts[0] ) )
                {
                    $_hostname = null;
                }
            }
        }

        if ( empty( $_hostname ) )
        {
            $_hostname = str_replace( '.dreamfactory.com', null, gethostname() );
        }

        return $_hostname;
    }

    /**
     * @param string $zone
     * @param bool   $partitioned
     *
     * @return bool|string
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
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getParameter( $name, $defaultValue = null )
    {
        try
        {
            $_value = $this->parameterBag->get( $name );
        }
        catch ( ParameterNotFoundException $_ex )
        {
            $_value = $defaultValue;
        }

        return $_value;
    }

    /**
     * Initialize the defaults for this application
     *
     * @param string $documentRoot
     * @param array  $parameters
     *
     * @return array
     */
    protected function _initializeDefaults( $documentRoot, $parameters = array() )
    {
        //  Global application defaults
        $_defaults = array_merge(
            array(
                'app.mode'            => $_appMode = ( 'cli' == PHP_SAPI ? 'console' : 'web' ),
                'app.host_name'       => $_hostname = $this->_determineHostName(),
                'app.base_path'       => $_basePath = dirname( $documentRoot ),
                'app.log_path'        => $_logPath = $_basePath . DIRECTORY_SEPARATOR . 'log',
                'app.config_path'     => $_configPath = $_basePath . DIRECTORY_SEPARATOR . 'config',
                'app.storage_path'    => $_storagePath = $_basePath . DIRECTORY_SEPARATOR . 'storage',
                'app.private_path'    => $_storagePath . DIRECTORY_SEPARATOR . '.private',
                'app.config_file'     => $_configFile = $_configPath . DIRECTORY_SEPARATOR . $_appMode . '.php',
                'app.run_id'          => $_runId = $this->_getAppRunId( $_hostname ),
                'app.app_path'        => $_basePath . DIRECTORY_SEPARATOR . 'web',
                'app.template_path'   => $_configPath . DIRECTORY_SEPARATOR . 'templates',
                'app.vendor_path'     => $_basePath . DIRECTORY_SEPARATOR . 'vendor',
                'app.hosted_instance' => IfSet::get( $parameters, 'app.hosted_instance' ),
            ),
            $parameters
        );

        $_defaults['app.log_file'] = IfSet::get( $parameters, 'app.log_file', $_appMode . '.' . $_hostname . '.log' );

        //  Create a logger if there isn't one
        if ( !$this->has( 'logger' ) )
        {
            $_handler = new StreamHandler( $_defaults['app.log_file'] );
            $_handler->setFormatter( new PaddedLineFormatter( null, null, true, true ) );
            $_logger = new Logger( 'app', array($_handler) );
            $this->set( 'logger', $_logger );
        }

        !$this->has( 'store' ) && $this->set( 'store', $this->_createStore( $_basePath ) );

        if ( !$this->has( 'resolver' ) )
        {
            $_resolver = new Resolver();

            $_resolver
                ->registerLocator( EnterpriseResources::ZONE, array($this, 'locateZone') )
                ->registerLocator( EnterpriseResources::PARTITION, array($this, 'locatePartition') )
                ->registerLocator( EnterpriseResources::INSTALL_ROOT, array($this, 'locateInstallRoot') );

            $this->set( 'resolver', $_resolver );
        }

        return $_defaults;
    }

    /**
     * @param string $basePath
     *
     * @return Flexistore|\CFileCache|\CMemCache
     */
    protected function _createStore( $basePath = null )
    {
        if ( null === ( $_cache = $this->_createMemcache() ) )
        {
            $_basePath = $basePath ?: $this->getParameter( 'app.base_path' );

            $_cachePath =
                $this->getParameter( 'app.hosted_instance' )
                    ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . '.dreamfactory' . DIRECTORY_SEPARATOR . '.cache'
                    :
                    $_basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '.private' . DIRECTORY_SEPARATOR . '.cache';

            if ( !is_dir( $_cachePath ) && false === mkdir( $_cachePath, 0777, true ) )
            {
                throw new \RuntimeException( 'Unable to locate a suitable pre-flight cache path.' );
            }

            if ( null === ( $_cache = $this->_createFileCache( $_cachePath ) ) )
            {
                throw new \RuntimeException( 'Unable to create pre-flight cache.' );
            }
        }

        return $_cache;
    }

    /**
     * @return \CMemCache|Flexistore|null
     */
    protected function _createMemcache()
    {
        $_cache = null;
        $_memcache = Includer::includeIfExists( $this->getParameter( 'app.config_path' . DIRECTORY_SEPARATOR . 'memcached.config.php' ) );

        if ( !empty( $_memcache ) )
        {
            try
            {
                $_cache = new \CMemCache();
                $_cache->setServers( $_memcache );
            }
            catch ( \Exception $_ex )
            {
                $_cache = Flexistore::createMemcachedStore( $_memcache );
            }

            return $_cache;
        }

        return null;
    }

    /**
     * @param string $cachePath
     *
     * @return \CFileCache|Flexistore
     */
    protected function _createFileCache( $cachePath )
    {
        //  Make a file cache...
        try
        {
            $_cache = new \CFileCache();
            $_cache->hashKey = false;
            $_cache->cachePath = $cachePath;
        }
        catch ( \Exception $_ex )
        {
            //  Try a flexistore if all else fails
            $_cache = Flexistore::createFileStore( $cachePath, null, static::DEFAULT_NAMESPACE );
        }

        return $_cache;
    }

    /**
     * @param string $hostName
     *
     * @return string
     */
    protected static function _getAppRunId( $hostName = null )
    {
        $_key = PHP_SAPI . '.' .
            IfSet::get( $_SERVER, 'REMOTE_ADDR', $hostName ?: getHostName() ) . '.' .
            IfSet::get( $_SERVER, 'HTTP_HOST', $hostName ?: getHostName() ) . '.';

        return hash( 'sha256', $_key );
    }

}


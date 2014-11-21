<?php
namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Enterprise\Storage\Resolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ScopeInterface;

/**
 * A general application container
 */
class AppBuilder implements ContainerInterface
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string
     */
    const DEFAULT_NAMESPACE = 'app';

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type ContainerBuilder
     */
    protected $_container;
    /**
     * @type Environment
     */
    protected $_environment;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param Environment $environment
     * @param array       $settings The settings needed to bootstrap and run the app
     */
    public function __construct( $environment, $settings = array() )
    {
        $this->_environment = new Environment();
        $this->_container = new ContainerBuilder( new ParameterBag( $settings ) );

        $this->_configure();
    }

    /**
     * Initializes the box
     */
    protected function _configure()
    {
        if ( true === $this->getParameter( 'app.debug', false ) )
        {
            ini_set( 'display_errors', true );
            defined( 'YII_DEBUG' ) or define( 'YII_DEBUG', true );

            //  php-error utility
            $this->getParameter( 'app.debug.use_php_error', false ) && function_exists( 'reportErrors' ) && reportErrors();
        }

        //  Get our base
        $this->_registerDefaultServices();

        //  Global application defaults
        $this->addParameters(
            array(
                'app.box'             => $this,
                'app.storage_path'    => $this->get( 'resolver' )->getStoragePath(),
                'app.private_path'    => $this->get( 'resolver' )->getPrivatePath(),
                'app.run_id'          => $this->_getAppRunId( $this->getParameter( 'app.hostname' ) ),
                'app.app_path'        => $this->getParameter( 'app.base_path' ) . DIRECTORY_SEPARATOR . 'web',
                'app.template_path'   => $this->getParameter( 'app.config_path' ) . DIRECTORY_SEPARATOR . 'templates',
                'app.vendor_path'     => $this->getParameter( 'app.base_path' ) . DIRECTORY_SEPARATOR . 'vendor',
                'app.hosted_instance' => $this->getParameter( 'app.hosted_instance' ),
                'app.request'         => $this->getParameter( 'app.request' ),
                'app.response'        => $this->getParameter( 'app.response' ),
            )
        );
    }

    /**
     * Register our default services
     */
    protected function _registerDefaultServices()
    {
        //  Storage resolver if we don't have one...
        if ( !$this->has( 'resolver' ) )
        {
            if ( null !== ( $_resolver = $this->getParameter( 'app.resolver' ) ) )
            {
                $this->_container->set( 'resolver', $_resolver );
            }
            else
            {
                $this->_container
                    ->register( 'resolver', 'DreamFactory\\Library\\Enterprise\\Storage\\Resolver' )
                    ->addArgument( '%resolver.hostname%' )
                    ->addArgument( '%resolver.mount_point%' )
                    ->addArgument( '%resolver.install_root%' );
            }
        }

        //  Create a logger if there isn't one
        if ( !$this->has( 'logger' ) )
        {
            $this->_container
                ->register( 'logger', 'Monolog\\Logger' )
                ->addArgument( '%logger.channel%' )
                ->addArgument( '%logger.handlers%' )
                ->addArgument( '%logger.processors%' );
        }

    }

    /**
     * Adds an array of values as parameters to the container if they do not exist
     *
     * @param array $parameters
     *
     * @return $this
     */
    public function addParameters( array $parameters = array() )
    {
        foreach ( $parameters as $_key => $_value )
        {
            if ( !$this->hasParameter( $_key ) )
            {
                $this->setParameter( $_key, $_value );
            }
        }

        return $this;
    }

    /**
     * @return string
     * @todo move to environment class
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
     * A getParameter method that returns a default value instead of an exception
     *
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getParameter( $name, $defaultValue = null )
    {
        if ( !$this->_container->hasParameter( $name ) )
        {
            $this->_container->setParameter( $name, $defaultValue );

            return $defaultValue;
        }

        return $this->_container->getParameter( $name );
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

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function setContainer( ContainerInterface $container = null )
    {
        $this->_container = $container;

        return $this;
    }

    /**
     * Sets a service.
     *
     * @param string $id      The service identifier
     * @param object $service The service instance
     * @param string $scope   The scope of the service
     *
     * @api
     * @return $this
     */
    public function set( $id, $service, $scope = self::SCOPE_CONTAINER )
    {
        $this->_container->set( $id, $service, $scope );

        return $this;
    }

    /**
     * Gets a service.
     *
     * @param string $id              The service identifier
     * @param int    $invalidBehavior The behavior when the service does not exist
     *
     * @return object The associated service
     *
     * @throws InvalidArgumentException if the service is not defined
     * @throws ServiceCircularReferenceException When a circular reference is detected
     * @throws ServiceNotFoundException When the service is not defined
     *
     * @see Reference
     *
     * @api
     */
    public function get( $id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE )
    {
        return $this->_container->get( $id, $invalidBehavior );
    }

    /**
     * Returns true if the given service is defined.
     *
     * @param string $id The service identifier
     *
     * @return bool    true if the service is defined, false otherwise
     *
     * @api
     */
    public function has( $id )
    {
        return $this->_container->has( $id );
    }

    /**
     * Checks if a parameter exists.
     *
     * @param string $name The parameter name
     *
     * @return bool    The presence of parameter in container
     *
     * @api
     */
    public function hasParameter( $name )
    {
        return $this->_container->hasParameter( $name );
    }

    /**
     * Sets a parameter.
     *
     * @param string $name  The parameter name
     * @param mixed  $value The parameter value
     *
     * @api
     * @return $this
     */
    public function setParameter( $name, $value )
    {
        $this->_container->setParameter( $name, $value );

        return $this;
    }

    /**
     * Enters the given scope
     *
     * @param string $name
     *
     * @api
     * @return $this
     */
    public function enterScope( $name )
    {
        $this->_container->enterScope( $name );

        return $this;
    }

    /**
     * Leaves the current scope, and re-enters the parent scope
     *
     * @param string $name
     *
     * @api
     * @return $this
     */
    public function leaveScope( $name )
    {
        $this->_container->leaveScope( $name );

        return $this;
    }

    /**
     * Adds a scope to the container
     *
     * @param ScopeInterface $scope
     *
     * @api
     * @return $this
     */
    public function addScope( ScopeInterface $scope )
    {
        $this->_container->addScope( $scope );

        return $this;
    }

    /**
     * Whether this container has the given scope
     *
     * @param string $name
     *
     * @return bool
     *
     * @api
     */
    public function hasScope( $name )
    {
        return $this->_container->hasScope( $name );
    }

    /**
     * Determines whether the given scope is currently active.
     *
     * It does however not check if the scope actually exists.
     *
     * @param string $name
     *
     * @return bool
     *
     * @api
     */
    public function isScopeActive( $name )
    {
        return $this->_container->isScopeActive( $name );
    }
}
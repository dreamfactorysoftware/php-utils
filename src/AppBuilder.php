<?php
namespace DreamFactory\Library\Utility;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * A general application container
 */
class AppBuilder extends ContainerBuilder
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string
     */
    const DEFAULT_NAMESPACE = 'app';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /** @inheritdoc */
    public function __construct( ParameterBagInterface $parameterBag )
    {
        parent::__construct( $parameterBag );

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
                'app.request'         => $this->get( 'environment' )->getRequest(),
                'app.response'        => $this->get( 'environment' )->getResponse(),
                'app.request_id'      => $this->get( 'environment' )->getRequestId(
                    $this->getParameter( 'environment.request_id.algorithm' ),
                    $this->getParameter( 'environment.request_id.entropy' )
                )
            )
        );
    }

    /**
     * Register our default services
     */
    protected function _registerDefaultServices()
    {
        //  Register the environment service
        $this
            ->register( 'environment', 'DreamFactory\\Library\\Utility\\Environment' )
            ->addArgument( '%environment.settings%' );

        //  Create a logger if there isn't one
        if ( !$this->has( 'logger' ) )
        {
            $this->register( 'logger', 'Monolog\\Logger' )
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
        $_url = getenv( 'EC2_URL' ) ?: null;/*Resolver::DEBUG_ZONE_URL;*/

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
        if ( !$this->hasParameter( $name ) )
        {
            $this->setParameter( $name, $defaultValue );

            return $defaultValue;
        }

        return parent::getParameter( $name );
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
        parent::set( $id, $service, $scope );

        return $this;
    }

}
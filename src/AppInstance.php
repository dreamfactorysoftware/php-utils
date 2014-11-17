<?php
namespace DreamFactory\Library\Utility;

use Kisma\Core\Components\Flexistore;
use Kisma\Core\Components\PaddedLineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
    //* Methods
    //******************************************************************************

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct( ParameterBagInterface $parameterBag = null )
    {
        parent::__construct( $this->_initializeDefaults( $parameterBag ) );

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
        $this->_initializeDefaults( $documentRoot );

        //	Load constants...
        static::includeIfExists( $this->getParameter( 'app.config_path' ) . DIRECTORY_SEPARATOR . 'constants.config.php', true, false );

        //  Initialize the app store
        static::_createStore( $_basePath );

        //	Create an alias for our configuration directory
        static::alias( 'application.config', $_configPath );
        static::alias( 'application.log', $_logPath );

        //	Load up any other aliases
        static::includeIfExists( $_configPath . '/aliases.config.php', false, true );

        /**
         * Application settings into persistent storage
         */
        $_runConfig = array(
            'app.run_id'              => static::$_appRunId,
            'app.app_path'            => $_basePath . '/web',
            'app.config_path'         => $_configPath,
            'app.log_path'            => $_logPath,
            'app.log_file'            => $_logFile,
            'app.template_path'       => $_configPath . '/templates',
            'app.vendor_path'         => $_basePath . '/vendor',
            CoreSettings::AUTO_LOADER => $autoloader,
            'app.app_class'           => $_appClass = $className ?: ( static::cli() ? 'CConsoleApplication' : 'CWebApplication' ),
            'app.config_file'         => $_configPath . '/' . $_appMode . '.php',
            //	Platform settings
            'platform.host_name'      => $_hostname,
            'platform.fabric_hosted'  => $_isFabric = static::hostedInstance(),
        );

        $_runConfig['app.storage_path'] = $_isFabric ? $_storagePath : false;
        $_runConfig['app.private_path'] = $_isFabric ? $_privatePath : false;

        //.........................................................................
        //. App Create & Run...
        //.........................................................................

        //	Copy configuration
        $_config = static::_loadConfig( $_configFile, $config, $enableConfigCache );

        //	Register the autoloader cuz Yii clobbers it somehow
        if ( $autoloader )
        {
            static::registerAutoloader(
                array(
                    $autoloader,
                    'loadClass'
                ),
                !$prependAutoloader
            );
        }

        $_runConfig['app.config'] = $_config;

        \Kisma::set( $_runConfig );
        static::appStoreSet( $_runConfig );

        //	Instantiate and run baby!
        /** @type \DreamFactory\Platform\Yii\Components\PlatformWebApplication|\DreamFactory\Platform\Yii\Components\PlatformConsoleApplication|\CWebApplication|\CConsoleApplication $_app */
        static::app( $_app = static::createApplication( $_appClass, $_config ) );

        if ( true === $autoRun )
        {
            $_app->run();
        }

        //	Just return the app
        return $_app;
    }

    /**
     * @param string|array $config
     *
     * @return array
     */
    protected static function _parseConfig( $config )
    {
        if ( !empty( $config ) )
        {
            if ( is_array( $config ) )
            {
                return $config;
            }

            if ( is_string( $config ) && file_exists( $config ) )
            {
                /** @noinspection PhpIncludeInspection */
                $_data = @include( $config );

                if ( is_array( $_data ) )
                {
                    return $_data;
                }
            }
        }

        return array();
    }

    /**
     * @param string       $configFile
     * @param array|string $config
     * @param bool         $enableConfigCache
     *
     * @throws \DreamFactory\Platform\Exceptions\InternalServerErrorException
     * @return array|null
     */
    protected static function _loadConfig( $configFile, $config = null, $enableConfigCache = true )
    {
        /**
         * If forced-caching is disabled, when we are in debug mode and/or config caching is enabled,
         * we always load the config from disk.
         */
        $_enableCache = $enableConfigCache && ( ( defined( YII_DEBUG ) || static::ENABLE_CONFIG_CACHE ) && !static::FORCE_CONFIG_CACHE );

        $_config = static::_parseConfig( $config );
        $_configFile = $configFile;

        if ( empty( $_config ) )
        {
            //  If cache enabled, then read from cache, which may be empty
            if ( $_enableCache )
            {
                if ( false === ( $_config = static::appStoreGet( 'app.config' ) ) || !is_array( $_config ) )
                {
                    $_config = array();
                }
            }

            //  If not cached, or we still don't have a configuration array, read from the file
            if ( empty( $_config ) )
            {
                /** @noinspection PhpIncludeInspection */
                if ( false === ( $_config = @require( $_configFile ) ) )
                {
                    throw new \RuntimeException( 'File system error reading configuration file "' . $_configFile . '"' );
                }

                //  If we got something, then cache it if enabled
                if ( $_enableCache )
                {
                    static::appStoreSet( 'app.config', $_config );
                }
            }
        }

        return $_config;
    }

    /**
     * Checks to see if the passed in data is an Url
     *
     * @param string $data
     *
     * @return boolean
     */
    public static function isUrl( $data )
    {
        return ( ( @parse_url( $data ) ) ? true : false );
    }

    /**
     * Checks for an empty variable. Useful because the PHP empty() function cannot be reliably used with overridden
     * __get methods.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function isEmpty( $value )
    {
        return empty( $value );
    }

    /**
     * {@InheritDoc}
     */
    public static function import( $alias, $forceInclude = false )
    {
        $_result = null;

        try
        {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            $_result = parent::import( $alias, $forceInclude );
        }
        catch ( \Exception $_ex )
        {
            //	See if composer can find it first...
        }

        return $_result;
    }

    /**
     * Also handle CHtml statics...
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public static function __callStatic( $name, $arguments )
    {
        if ( method_exists( '\\CHtml', $name ) )
        {
            return call_user_func_array(
                array('\\CHtml', $name),
                $arguments
            );
        }

        throw new \BadMethodCallException( 'Method "' . __CLASS__ . '::' . $name . '()" not found.' );
    }

    /**
     * Returns the current user identity.
     *
     * @return \CUserIdentity
     */
    public static function identity()
    {
        return static::component( 'user', false );
    }

    /**
     * Returns the current request. Equivalent of {@link CApplication::getRequest}
     *
     * @see \CApplication::getRequest
     * @see \Symfony\Component\HttpFoundation\Request
     *
     * @param bool $yiiVersion If true (default), the YII request is returned. Otherwise a Symfony Request is returned
     *
     * @return \CHttpRequest|\Symfony\Component\HttpFoundation\Request
     */
    public static function request( $yiiVersion = true )
    {
        return $yiiVersion ? static::app()->getRequest() : static::requestObject();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public static function requestObject()
    {
        return static::app()->getRequestObject();
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public static function response( $response = null )
    {
        return static::responseObject( $response );
    }

    /**
     * Getter/setter
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response|\DreamFactory\Platform\Yii\Components\PlatformWebApplication
     */
    public static function responseObject( $response = null )
    {
        //  Act like a setter
        if ( null !== $response )
        {
            return static::app()->setResponseObject( $response );
        }

        //  But I'm a getter too
        return static::app()->getResponseObject();
    }

    //********************************************************************************
    //* Yii Convenience Mappings
    //********************************************************************************

    /**
     * Shorthand version of Yii::app() with caching. Ya know, for speed!
     *
     * @param \CApplication $app
     *
     * @return \DreamFactory\Platform\Yii\Components\PlatformWebApplication|\DreamFactory\Platform\Yii\Components\PlatformConsoleApplication|\CConsoleApplication|\CWebApplication
     */
    public static function app( $app = null )
    {
        /** @var $_thisApp \CApplication|\CWebApplication|\CConsoleApplication */
        static $_thisApp = null;

        if ( false === $app || null !== $_thisApp )
        {
            return $_thisApp;
        }

        $_thisApp = $app ?: parent::app();

        //	Non-CLI requests have clientScript and a user maybe
        if ( $_thisApp )
        {
            if ( static::cli() )
            {
                static::$_clientScript = $_thisApp->getComponent( 'clientScript', false );
                static::$_thisUser = $_thisApp->getComponent( 'user', false );
            }

            static::$_thisRequest = $_thisApp->getComponent( 'request', false );
            static::$_appParameters = $_thisApp->getParams();

            //  Save params to store...
            static::appStoreSet( 'app.params', static::$_appParameters );
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
        if ( empty( static::$_appParameters ) )
        {
            if ( null !== ( $_app = static::app() ) )
            {
                static::$_appParameters = $_app->getParams();
            }
            else
            {
                static::$_appParameters = array();
            }
        }

        if ( null !== $prefix )
        {
            $_parameters = array();

            if ( false === $regex )
            {
                //	Make sure a trailing dot is added to prefix...
                $prefix = trim( strtolower( rtrim( $prefix, ' .' ) . '.' ) );
            }

            foreach ( static::$_appParameters as $_key => $_value )
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

        return static::$_appParameters;
    }

    /**
     * @param string $db
     *
     * @return \PDO
     */
    public static function pdo( $db = 'db' )
    {
        return static::db( $db )->getPdoInstance();
    }

    /**
     * Shorthand version of Yii::app()->getController()
     *
     * @return \CController|\CBaseController
     */
    public static function controller()
    {
        return static::app()->getController();
    }

    /**
     * Shorthand version of Yii::app()->getName()
     *
     * @param bool $notEncoded
     *
     * @return string
     */
    public static function appName( $notEncoded = false )
    {
        return $notEncoded ? static::app()->name : \CHtml::encode( static::app()->name );
    }

    /**
     * Convenience method returns the current page title
     *
     * @see \CController::pageTitle
     * @see \CHtml::encode
     *
     * @param $notEncoded bool
     *
     * @return string
     */
    public static function pageTitle( $notEncoded = false )
    {
        return $notEncoded
            ? static::controller()->getPageTitle()
            : \CHtml::encode(
                static::controller()->getPageTitle()
            );
    }

    /**
     * Convenience method Returns the base url of the current app
     *
     * @param $absolute bool
     *
     * @return string
     */
    public static function baseUrl( $absolute = false )
    {
        return static::app()->getBaseUrl( $absolute );
    }

    /**
     * Convenience method Returns the base path of the current app
     *
     * @param string $subPath
     *
     * @return string
     */
    public static function basePath( $subPath = null )
    {
        return static::app()->getBasePath() . ( null !== $subPath ? '/' . ltrim( $subPath, '/' ) : null );
    }

    /***
     * Retrieves and caches the Yii ClientScript object
     *
     * @return CClientScript
     */
    public static function clientScript()
    {
        return static::app()->getClientScript();
    }

    /**
     * Terminates the application.
     * This method replaces PHP's exit() function by calling {@link onEndRequest} before exiting.
     *
     * @param integer $status exit status (value 0 means normal exit while other values mean abnormal exit).
     * @param boolean $exit   whether to exit the current request. This parameter has been available since version
     *                        1.1.5. It defaults to true, meaning the PHP's exit() function will be called at the end
     *                        of this method.
     *
     * @return bool
     */
    public static function end( $status = 0, $exit = true )
    {
        static::app()->end( $status, $exit );

        return false;
    }

    /**
     * @param string $id
     * @param bool   $createIfNull
     *
     * @return \CComponent The component, if found
     */
    public static function component( $id, $createIfNull = true )
    {
        return static::app()->getComponent( $id, $createIfNull );
    }

    /**
     * @param string $name
     *
     * @return \CDbConnection the database connection
     */
    public static function db( $name = 'db' )
    {
        return static::component( $name );
    }

    /**
     * Registers a javascript file.
     *
     * @internal param $string \URL of the javascript file
     * @internal param $integer \the position of the JavaScript code. Valid values include the following:
     * <ul>
     * <li>CClientScript::POS_HEAD : the script is inserted in the head section right before the title element.</li>
     * <li>CClientScript::POS_BEGIN : the script is inserted at the beginning of the body section.</li>
     * <li>CClientScript::POS_END : the script is inserted at the end of the body section.</li>
     * </ul>
     *
     * @param string|array $urlList
     * @param int          $pagePosition
     *
     * @return CClientScript
     */
    public static function scriptFile( $urlList, $pagePosition = CClientScript::POS_HEAD )
    {
        //	Need external library?
        foreach ( Option::clean( $urlList ) as $_url )
        {
            if ( !static::clientScript()->isScriptFileRegistered( $_url ) )
            {
                static::clientScript()->registerScriptFile( $_url, $pagePosition );
            }
        }

        return static::clientScript();
    }

    /**
     * Registers a CSS file
     *
     * @param string $urlList
     * @param string $media that the CSS file should be applied to. If empty, it means all media types.
     *
     * @return CClientScript|null|string
     */
    public static function cssFile( $urlList, $media = null )
    {
        foreach ( Option::clean( $urlList ) as $_url )
        {
            if ( !static::clientScript()->isCssFileRegistered( $_url ) )
            {
                static::clientScript()->registerCssFile( $_url, $media );
            }
        }

        return static::clientScript();
    }

    /**
     * Registers a piece of CSS code.
     *
     * @param string $id    ID that uniquely identifies this piece of CSS code
     * @param string $css   The CSS code
     * @param string $media Media that the CSS code should be applied to. If empty, it means all media types.
     *
     * @return CClientScript|null
     * @access public
     * @static
     */
    public static function css( $id, $css, $media = null )
    {
        if ( !static::clientScript()->isCssRegistered( $id ) )
        {
            static::clientScript()->registerCss( $id, $css, $media );
        }

        return static::clientScript();
    }

    /**
     * Registers a piece of javascript code.
     *
     * @param string  $id       ID that uniquely identifies this piece of JavaScript code
     * @param string  $script   the javascript code
     * @param integer $position the position of the JavaScript code. Valid values include the following:
     *                          <ul>
     *                          <li>CClientScript::POS_HEAD : the script is inserted in the head section right before
     *                          the title element.</li>
     *                          <li>CClientScript::POS_BEGIN : the script is inserted at the beginning of the body
     *                          section.</li>
     *                          <li>CClientScript::POS_END : the script is inserted at the end of the body
     *                          section.</li>
     *                          <li>CClientScript::POS_LOAD : the script is inserted in the window.onload()
     *                          function.</li>
     *                          <li>CClientScript::POS_READY : the script is inserted in the jQuery's ready
     *                          function.</li>
     *                          </ul>
     *
     * @return CClientScript|null|string
     * @access public
     * @static
     */
    public static function script( $id, $script, $position = CClientScript::POS_READY )
    {
        if ( !static::clientScript()->isScriptRegistered( $id ) )
        {
            static::clientScript()->registerScript(
                $id,
                $script,
                $position
            );
        }

        return static::clientScript();
    }

    /**
     * Registers a meta tag that will be inserted in the head section (right before the title element) of the resulting
     * page.
     *
     * @param string $content        content attribute of the meta tag
     * @param string $name           name attribute of the meta tag. If null, the attribute will not be generated
     * @param string $httpEquivalent http-equiv attribute of the meta tag. If null, the attribute will not be generated
     * @param array  $attributes     other options in name-value pairs (e.g. 'scheme', 'lang')
     *
     * @return CClientScript|null
     * @access public
     * @static
     */
    public static function metaTag( $content, $name = null, $httpEquivalent = null, $attributes = array() )
    {
        static::clientScript()->registerMetaTag( $content, $name, $httpEquivalent, $attributes );

        return static::clientScript();
    }

    /**
     * Creates a relative URL based on the given controller and action information.
     *
     * @param string $route     the URL route. This should be in the format of 'ControllerID/ActionID'.
     * @param array  $options   additional GET parameters (name=>value). Both the name and value will be URL-encoded.
     * @param string $ampersand the token separating name-value pairs in the URL.
     *
     * @return string the constructed URL
     */
    public static function url( $route, $options = array(), $ampersand = '&' )
    {
        return static::app()->createUrl( $route, $options, $ampersand );
    }

    /**
     * Returns the current user. Equivalent of {@link CWebApplication::getUser}
     *
     * @return \CWebUser
     */
    public static function user()
    {
        return static::app()->getUser();
    }

    /**
     * Returns boolean indicating if user is logged in or not
     *
     * @return boolean
     */
    public static function guest()
    {
        return static::user()->getIsGuest();
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

        return Option::get( $_parameters, $paramName, $defaultValue );
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
        static::appStoreSet(
            'app.params',
            static::$_appParameters = static::app()->getParams()
        );
    }

    /**
     * Convenience access to CAssetManager::publish()
     *
     * Publishes a file or a directory.
     * This method will copy the specified asset to a web accessible directory
     * and return the URL for accessing the published asset.
     * <ul>
     * <li>If the asset is a file, its file modification time will be checked
     * to avoid unnecessary file copying;</li>
     * <li>If the asset is a directory, all files and subdirectories under it will
     * be published recursively. Note, in this case the method only checks the
     * existence of the target directory to avoid repetitive copying.</li>
     * </ul>
     *
     * @param string  $path       the asset (file or directory) to be published
     * @param boolean $hashByName whether the published directory should be named as the hashed basename.
     *                            If false, the name will be the hashed dirname of the path being published.
     *                            Defaults to false. Set true if the path being published is shared among
     *                            different extensions.
     * @param integer $level      level of recursive copying when the asset is a directory.
     *                            Level -1 means publishing all subdirectories and files;
     *                            Level 0 means publishing only the files DIRECTLY under the directory;
     *                            level N means copying those directories that are within N levels.
     *
     * @return string an absolute URL to the published asset
     *
     * @throws \CException if the asset to be published does not exist.
     * @see \CAssetManager::publish
     */
    public static function publishAsset( $path, $hashByName = false, $level = -1 )
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return static::app()->getAssetManager()->publish( $path, $hashByName, $level );
    }

    /**
     * Performs a redirect. See {@link CHttpRequest::redirect}
     *
     * @param string  $url
     * @param boolean $terminate
     * @param int     $statusCode
     *
     * @see \CHttpRequest::redirect
     */
    public static function redirect( $url, $terminate = true, $statusCode = 302 )
    {
        static::request()->redirect(
            is_array( $url ) ? $url : static::url( $url ),
            $terminate,
            $statusCode
        );
    }

    /**
     * Returns the details about the error that is currently being handled.
     * The error is returned in terms of an array, with the following information:
     * <ul>
     * <li>code - the HTTP status code (e.g. 403, 500)</li>
     * <li>type - the error type (e.g. 'CHttpException', 'PHP Error')</li>
     * <li>message - the error message</li>
     * <li>file - the name of the PHP script file where the error occurs</li>
     * <li>line - the line number of the code where the error occurs</li>
     * <li>trace - the call stack of the error</li>
     * <li>source - the context source code where the error occurs</li>
     * </ul>
     *
     * @return array the error details. Null if there is no error.
     */
    public static function currentError()
    {
        $_handler = static::app()->getErrorHandler();

        if ( !empty( $_handler ) )
        {
            return $_handler->getError();
        }

        return null;
    }

    /**
     * Determine if PHP is running CLI mode or not
     *
     * @return boolean True if currently running in CLI
     */
    public static function cli()
    {
        return ( 'cli' == PHP_SAPI );
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
            static::setPathOfAlias( $alias, $path );

            return $path;
        }

        $_path = static::getPathOfAlias( $alias );

        if ( null !== $morePath )
        {
            $_path = trim(
                rtrim( $_path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . ltrim( $morePath, DIRECTORY_SEPARATOR )
            );
        }

        return $_path;
    }

    /**
     * @return boolean whether this is POST request.
     */
    public static function postRequest()
    {
        static $_is = null;

        if ( null === $_is )
        {
            $_is = static::request()->getIsPostRequest();
        }

        return $_is;
    }

    /**
     * @return boolean whether this is PUT request.
     */
    public static function putRequest()
    {
        static $_is = null;

        if ( null === $_is )
        {
            $_is = static::request()->getIsPutRequest();
        }

        return $_is;
    }

    /**
     * @return boolean whether this is DELETE request.
     */
    public static function deleteRequest()
    {
        static $_is = null;

        if ( null === $_is )
        {
            $_is = static::request()->getIsDeleteRequest();
        }

        return $_is;
    }

    /**
     * @return boolean whether this is DELETE request.
     */
    public static function ajaxRequest()
    {
        static $_is = null;

        if ( null === $_is )
        {
            $_is = static::request()->getIsAjaxRequest();
        }

        return $_is;
    }

    /**
     * Serializer that can handle SimpleXmlElement objects
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function serialize( $value )
    {
        try
        {
            if ( $value instanceof \SimpleXMLElement )
            {
                return $value->asXML();
            }

            if ( is_object( $value ) )
            {
                return \serialize( $value );
            }
        }
        catch ( \Exception $_ex )
        {
        }

        return $value;
    }

    /**
     * Unserializer that can handle SimpleXmlElement objects
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function unserialize( $value )
    {
        try
        {
            if ( static::serialized( $value ) )
            {
                if ( $value instanceof \SimpleXMLElement )
                {
                    return \simplexml_load_string( $value );
                }

                return \unserialize( $value );
            }
        }
        catch ( \Exception $_ex )
        {
        }

        return $value;
    }

    /**
     * Tests if a value needs unserialization
     *
     * @param mixed $value
     *
     * @return boolean
     */
    public static function serialized( $value )
    {
        $_result = @\unserialize( $value );

        return !( false === $_result && $value != \serialize( false ) );
    }

    /**
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public static function getState( $name, $defaultValue = null )
    {
        return static::app()->getUser()->getState( $name, $defaultValue );
    }

    /**
     * @param string $name
     * @param mixed  $value The value to store
     * @param mixed  $defaultValue
     *
     * @return \CConsoleApplication|\CWebApplication
     */
    public static function setState( $name, $value, $defaultValue = null )
    {
        static::app()->getUser()->setState( $name, $value, $defaultValue );
    }

    /**
     * @param string $name
     */
    public static function clearState( $name )
    {
        static::app()->getUser()->setState( $name, null, null );
    }

    /**
     * Stores a flash message.
     * A flash message is available only in the current and the next requests.
     *
     * @param string $key
     * @param string $message
     * @param string $defaultValue
     *
     * @return \CConsoleApplication|\CWebApplication
     */
    public static function setFlash( $key, $message = null, $defaultValue = null )
    {
        static::app()->getUser()->setFlash( $key, $message, $defaultValue );
    }

    /**
     * Gets a stored flash message
     * A flash message is available only in the current and the next requests.
     *
     * @param string  $key
     * @param mixed   $defaultValue
     * @param boolean $delete If true, delete this flash message after accessing it.
     *
     * @return string
     */
    public static function getFlash( $key, $defaultValue = null, $delete = true )
    {
        return static::app()->getUser()->getFlash( $key, $defaultValue, $delete );
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
     * Requires a file only if it exists
     *
     * @param string $file    the absolute /path/to/file.php
     * @param bool   $require use "require" instead of "include"
     * @param bool   $once    use "include_once" or "require_once" if $require is true
     *
     * @return bool|mixed
     */
    public static function includeIfExists( $file, $require = false, $once = false )
    {
        return Includer::includeIfExists( $file, $require, $once );
    }

    /**
     * @return bool True if this is a multi-tenant installation
     */
    public static function fabricHosted()
    {
        return static::hostedInstance();
    }

    /**
     * @return bool True if this is a multi-tenant installation
     */
    public static function hostedInstance()
    {
        static $_hosted = null;

        if ( null !== $_hosted )
        {
            return $_hosted;
        }

        return $_hosted = ( static::DEFAULT_DOC_ROOT == FilterInput::server( 'DOCUMENT_ROOT' ) && file_exists( static::FABRIC_MARKER ) );
    }

    //******************************************************************************
    //* App Store/Cache
    //******************************************************************************

    /**
     * Flushes the config from the cache
     *
     * @param string $key
     *
     * @return mixed
     */
    public static function flushConfig( $key = 'app.config' )
    {
        return static::appStoreDelete( $key );
    }

    /**
     * @param string $hostName
     *
     * @return string
     */
    protected static function _setAppRunId( $hostName = null )
    {
        $_key =
            PHP_SAPI .
            '.' .
            Option::server( 'REMOTE_ADDR', $hostName ?: getHostName() ) .
            '.' .
            Option::server( 'HTTP_HOST', $hostName ?: getHostName() ) .
            '.';

        $_hash = hash( 'sha256', $_key );

        static::$_logCacheKeys && Log::debug( 'cache key "' . $_key . '" hashed: ' . $_hash );

        return static::$_appRunId = $_hash;
    }

    /**
     * Ya gotta keep 'em separated...
     *
     * @param string $key
     *
     * @return string
     */
    protected static function _cacheKey( $key )
    {
        return static::$_appRunId . '.' . $key;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return bool|bool[]
     */
    public static function appStoreSet( $key, $value = null, $ttl = self::CACHE_TTL )
    {
        $_store = static::getAppStore();

        if ( is_array( $key ) )
        {
            $_result = array();

            foreach ( $key as $_key => $_value )
            {
                $_result[] = $_store->set( static::_cacheKey( $_key ), $_value, $ttl );
            }

            return $_result;
        }

        return $_store->set( static::_cacheKey( $key ), $value, $ttl );
    }

    /**
     * @param string $key
     * @param mixed  $defaultValue
     * @param bool   $remove
     *
     * @return mixed|mixed[]
     */
    public static function appStoreGet( $key, $defaultValue = null, $remove = false )
    {
        $_store = static::getAppStore();
        $_id = static::_cacheKey( $key );

        if ( method_exists( $_store, 'fetch' ) )
        {
            if ( false === ( $_data = $_store->fetch( $_id ) ) )
            {
                if ( !$remove )
                {
                    $_store->save( $_id, $_data = $defaultValue );
                }
            }
            elseif ( $remove )
            {
                $_store->delete( $_id );
            }
        }
        else
        {
            $_data = $defaultValue;

            if ( $_store instanceof \CCache )
            {
                if ( false !== ( $_value = $_store->get( static::_cacheKey( $key ) ) ) )
                {
                    $_data = $_value;
                }
            }
            else
            {
                $_data = $_store->get( $key, $defaultValue, $remove );
            }
        }

        return $_data;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function appStoreContains( $key )
    {
        $_store = static::getAppStore();

        if ( method_exists( $_store, 'contains' ) )
        {
            return $_store->contains( static::_cacheKey( $key ) );
        }
        else if ( method_exists( $_store, 'fetch' ) )
        {
            return false !== $_store->fetch( static::_cacheKey( $key ) );
        }

        return false !== $_store->get( static::_cacheKey( $key ) );
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function appStoreDelete( $key )
    {
        return static::getAppStore()->delete( static::_cacheKey( $key ) );
    }

    /**
     * @return bool
     */
    public static function appStoreDeleteAll()
    {
        $_store = static::getAppStore();

        if ( method_exists( $_store, 'flush' ) )
        {
            return $_store->flush();
        }
        else if ( method_exists( $_store, 'deleteAll' ) )
        {
            return static::getAppStore()->deleteAll();
        }

        return false;
    }

    /**
     * @return array
     */
    public static function appStoreStats()
    {
        $_store = static::getAppStore();

        if ( method_exists( $_store, 'getStats' ) )
        {
            return $_store->getStats();
        }

        return array();
    }

    /**
     * @return Flexistore|CacheProvider|Cache|\CCache
     */
    public static function getAppStore()
    {
        return static::_initAppStore( static::$_basePath );
    }

    /**
     * Initialize the defaults for this application
     *
     * @param string $documentRoot
     * @param array $parameters
     */
    protected function _initializeDefaults( $documentRoot, $parameters = array() )
    {
        $_defaults = array(
            'app.mode'            => $_appMode = static::cli() ? 'console' : 'web',
            'app.host_name'       => $_hostname = $this->_determineHostName(),
            'app.base_path'       => $_basePath = dirname( $documentRoot ),
            'app.log_path'        => $_logPath = $_basePath . DIRECTORY_SEPARATOR . 'log',
            'app.config_path'     => $_configPath = $_basePath . DIRECTORY_SEPARATOR . 'config',
            'app.storage_path'    => $_storagePath = $_basePath . DIRECTORY_SEPARATOR . 'storage',
            'app.private_path'    => $_storagePath . DIRECTORY_SEPARATOR . '.private',
            'app.config_file'     => $_configFile = $_configPath . DIRECTORY_SEPARATOR . $_appMode . '.php',
            'app.run_id'          => $_runId = static::_setAppRunId( $_hostname ),
            'app.app_path'        => $_basePath . DIRECTORY_SEPARATOR . 'web',
            'app.template_path'   => $_configPath . DIRECTORY_SEPARATOR . 'templates',
            'app.vendor_path'     => $_basePath . DIRECTORY_SEPARATOR . 'vendor',
            'app.hosted_instance' => $this->hostedInstance(),
        );

        if ( null === ( $_logFile = $this->getParameter( 'app.log_file', static::NULL_ON_INVALID_REFERENCE ) ) )
        {
            $_defaults['app.log_file'] = $_logFile = $_appMode . '.' . $_hostname . '.log';
        }

        //  Create a logger if there isn't one
        try
        {
            $_logger = $this->get( 'logger' );
        }
        catch ( \Exception $_ex )
        {
            $_handler = new StreamHandler( $_logFile );
            $_handler->setFormatter( new PaddedLineFormatter( null, null, true, true ) );
            $_logger = new Logger( 'app', array($_handler) );
        }

        $this->set( 'logger', $_logger );

        foreach ( $_defaults as $_key => $_value )
        {
            $this->setParameter( $_key, $_value );
        }

        $this->set( 'store', $this->_createStore( $_basePath ) );
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
}


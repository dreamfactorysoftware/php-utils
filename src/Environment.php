<?php namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Utility\Interfaces\EnvironmentProviderLike;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains helpers that discover information about the current runtime environment
 */
class Environment extends ParameterBag implements EnvironmentProviderLike, ContainerAwareInterface
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /** @type string */
    const KEY_HOSTED_INSTANCE = 'environment.hosted_instance';
    /** @type array A list of root directories to use */
    const KEY_VALID_ROOTS = 'environment.valid_roots';
    /** @type array A list of additional root directories to use */
    const KEY_ADDITIONAL_ROOTS = 'environment.additional_roots';
    /**
     * @type string Hash algorithm to use for partitioning
     */
    const DEFAULT_DATA_STORAGE_HASH = 'sha256';

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
    /**
     * @type ContainerInterface
     */
    protected $_container;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /** @inheritdoc */
    public function __construct(array $settings = [])
    {
        $this->_request = IfSet::get($settings, 'request', Request::createFromGlobals());
        $this->_response = IfSet::get($settings, 'response', Response::create());
        $this->_container = null;

        parent::__construct($settings);
    }

    /** @inheritdoc */
    public function getUserName()
    {
        $_key = static::KEY_USER_NAME;

        if (null !== ($_value = $this->getOrDefault($_key))) {
            return $_value;
        }

        //  List of places to get users in order
        $_value = null;

        $_users = [
            getenv('USER'),
            isset($_SERVER, $_SERVER['USER']) ? $_SERVER['USER'] : false,
            get_current_user(),
        ];

        foreach ($_users as $_user) {
            if (!empty($_user)) {
                $_value = $_user;
                break;
            }
        }

        if (empty($_value)) {
            throw new \LogicException('Cannot determine current user name.');
        }

        $this->set($_key, $_value);

        return $_value;
    }

    /** @inheritdoc */
    public function getHostname($fqdn = true)
    {
        $_key = static::KEY_HOSTNAME;

        if (null !== ($_value = $this->getOrDefault($_key))) {
            return $_value;
        }

        //	Figure out my name
        $_value = $this->getRequest()->getHttpHost();

        if (empty($_value)) {
            $_value = gethostname();
        }

        $_parts = explode('.', $_value);
        $_value = $fqdn ? $_value : (count($_parts) ? $_parts[0] : $_value);

        $this->set($_key, $_value);
        $this->set($_key . '.parts', $_parts);

        return $_value;
    }

    /** @inheritdoc */
    public function getTempPath($subPath = null)
    {
        $_key = static::KEY_TEMP_PATH;

        if (null !== ($_value = $this->getOrDefault($_key))) {
            return $_value;
        }

        $_value =
            FileSystem::ensurePath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . ltrim($subPath, DIRECTORY_SEPARATOR));

        $this->set($_key, $_value);

        return $_value;
    }

    /** @inheritdoc */
    public function getRequestId($algorithm = self::DEFAULT_DATA_STORAGE_HASH, $entropy = null)
    {
        $_hostname = $this->getHostname();

        return hash($algorithm,
            PHP_SAPI . '_' . $this->get('request')->server->get('remote-addr',
                $_hostname) . '_' . $_hostname . ($entropy ? '_' . $entropy : null));
    }

    /** @inheritdoc */
    public function getInstallPath($startPath = null, $useFileDir = false)
    {
        $_key = static::KEY_INSTALL_PATH;

        if (null !== ($_value = $this->getOrDefault($_key))) {
            return $_value;
        }

        $_path = $startPath ?: ($useFileDir ? __DIR__ : getcwd());

        while (true) {
            if (file_exists($_path . DIRECTORY_SEPARATOR . 'composer.json') && is_dir($_path . DIRECTORY_SEPARATOR . 'vendor')) {
                $this->set($_key, $_path);
                break;
            }

            $_path = dirname($_path);

            if (empty($_path) || $_path == DIRECTORY_SEPARATOR) {
                throw new \RuntimeException('Platform installation path not found.');
            }
        }

        $this->set($_key, $_path);

        return $_path;
    }

    /**
     * @param string $zone
     * @param bool   $partitioned
     *
     * @return bool|string
     * @todo convert to resource locator
     */
    public function locateZone($zone = null, $partitioned = false)
    {
        $_key = static::KEY_ZONE;

        if (null !== ($_value = $zone ?: $this->getOrDefault($_key))) {
            return $_value;
        }

        //  Zones only apply to partitioned layouts
        if (!$partitioned) {
            $_zone = false;
        } else {
            //  Try ec2...
            $_url = getenv('EC2_URL') ?: null;

            //  Not on EC2, we're something else
            if (empty($_url)) {
                return false;
            }

            //  Get the EC2 zone of this instance from the url
            $_zone = str_ireplace(['https://', '.amazonaws.com'], null, $_url);
            $this->set($_key, $_zone);
        }

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
    public function locatePartition($storageId, $partitioned)
    {
        return $partitioned ? substr($storageId, 0, 2) : false;
    }

    /**
     * @param string $yes Optional string to return if in PHP_SAPI is 'cli'
     * @param string $no  Optional string to return if in PHP_SAPI is NOT 'cli'
     *
     * @return string|bool
     */
    public function cli($yes = null, $no = null)
    {
        return 'cli' === PHP_SAPI ? ($yes ?: true) : ($no ?: false);
    }

    /**
     * @return Resolver
     */
    public function getResolver()
    {
        if ($this->_container && null !== ($_resolver =
                $this->_container->get('resolver', ContainerInterface::NULL_ON_INVALID_REFERENCE))
        ) {
            return $_resolver;
        }

        throw new \RuntimeException('No value for "storage resolver" has been set.');
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @inheritdoc
     * @return $this
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->_container = $container;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $defaultValue
     * @param bool   $remove
     *
     * @return bool|mixed
     */
    public function getOrDefault($key, $defaultValue = null, $remove = false)
    {
        $_value = $this->has($key) ? $this->get($key) : $defaultValue;
        $remove && $this->remove($key);

        return $_value;
    }

    /**
     * Override of set() that returns $this
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($name, $value)
    {
        parent::set($name, $value);

        return $this;
    }
}
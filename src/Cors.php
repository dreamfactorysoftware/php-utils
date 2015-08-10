<?php namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Utility\Enums\Verbs;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Various CORS functions that can be pulled into filters or events
 */
class Cors
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @var string Indicates all is allowed
     */
    const ALLOW_ALL = '*';
    /**
     * @var string The default allowed HTTP verbs
     */
    const DEFAULT_ALLOWED_VERBS = 'GET,POST,PUT,DELETE,PATCH,MERGE,COPY,OPTIONS';
    /**
     * @var string The default allowed HTTP headers
     *
     * Tunnelling verb overrides:
     *      X-Http-Method (Microsoft)
     *      X-Http-Method-Override (Google/GData)
     *      X-Method-Override (IBM)
     */
    const DEFAULT_ALLOWED_HEADERS = 'content-type,x-requested-with,x-application-name,x-http-method,x-http-method-override,x-method-override';
    /**
     * @var int The default number of seconds to allow this to be cached. Default is 15 minutes.
     */
    const DEFAULT_MAX_AGE = 900;
    /**
     * @var string The private CORS configuration file
     */
    const DEFAULT_CONFIG_FILE = '/cors.config.json';
    /**
     * @var string The cache key for CORS config caching
     */
    const WHITELIST_KEY = 'cors.config';
    /**
     * @type int The number of seconds we'll cache CORS data
     */
    const CACHE_TTL = 300;

    //********************************************************************************
    //* Members
    //********************************************************************************

    /**
     * @type LoggerInterface
     */
    protected $_logger;
    /**
     * @type Request The inbound request
     */
    protected $_request;
    /**
     * @type array Allowed HTTP headers
     */
    protected $_headers;
    /**
     * @type array Allowed HTTP methods
     */
    protected $_verbs;
    /**
     * @type array An indexed array of white-listed host names (ajax.example.com or foo.bar.com or just bar.com)
     */
    protected $_whitelist;
    /**
     * @type string The CORS config file, if any
     */
    protected $_configFile;
    /**
     * @type string The origin of the request
     */
    protected $_requestOrigin = null;
    /**
     * @type string The verb of the request
     */
    protected $_requestVerb = null;
    /**
     * @type bool True if this request utilizes CORS
     */
    protected $_corsRequest = false;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Constructor
     *
     * @param string  $configFile The CORS config file, if any
     * @param Request $request    Optional request object. If not given, one will be created
     */
    public function __construct($configFile = null, $request = null)
    {
        //  Set defaults
        $this->_configFile = $configFile;
        $this->_request = $request ?: Request::createFromGlobals();
        $this->_headers = explode(',', static::DEFAULT_ALLOWED_HEADERS);
        $this->_verbs = explode(',', static::DEFAULT_ALLOWED_VERBS);

        //  See if we even need CORS
        $this->_requestOrigin = trim($this->_request->headers->get('http-origin'));
        $this->_requestVerb = $this->_request->getMethod();
        $this->_corsRequest = !empty($this->_requestOrigin);

        //  Load whitelist from file
        // if there...
        if ($this->_configFile) {
            $this->_whitelist = JsonFile::decodeFile($configFile);
        }
    }

    /**
     * @param array|bool $whitelist     Set to "false" to reset the internal method cache.
     * @param bool       $returnHeaders If true, the headers are return in an array and NOT sent
     * @param bool       $sendHeaders   If false, the headers will NOT be sent. Defaults to true. $returnHeaders takes
     *                                  precedence
     *
     * @return bool|array
     */
    public function addHeaders($whitelist = [], $returnHeaders = false, $sendHeaders = true)
    {
        static $_cache = [];

        //	Reset the cache before processing...
        if (false === $whitelist) {
            if (!empty($_cache)) {
                $_cache = [];

                $this->_logger->debug('internal cache reset');
            }

            return true;
        }

        //	Find out if we actually received an origin header
        if (null === ($_origin = trim(strtolower($this->_request->headers->get('http-origin'))))) {
            //  No origin header, no CORS...
            $this->_logger->debug('no origin received.');

            return $returnHeaders ? [] : false;
        }

        //  Only bail if origin is empty or == 'file://'. Ya know, for Javascript!
        if ('file://' == $_origin || empty($_origin)) {
            return $returnHeaders ? [] : !empty($_origin);
        }

        $_isStar = false;
        $_requestUri = $this->_request->getSchemeAndHttpHost();

        $this->_logger->debug('origin received: ' . $_origin);

        if (false === ($_originParts = Uri::parse($_origin))) {
            //	Not parse-able, set to itself, check later (testing from local files - no session)?
            $this->_logger->warning('unable to parse received origin: [' . $_origin . ']');

            $_originParts = $_origin;
        }

        $_originUri = is_array($_originParts) ? Uri::normalize($_originParts) : static::ALLOW_ALL;

        $_key = sha1($_requestUri . '_' . $_originUri);

        $this->_logger->debug('origin URI "' . $_originUri . '" assigned key "' . $_key . '"');

        //  If we have a cache, refresh local from there
        if ($this->_cache && empty($_cache)) {
            if (false === ($_cache = $this->_cache->fetch($this->_id . '.headers'))) {
                $_cache = [];
            }
        }

        //	Not in cache, check it out...
        if (!array_key_exists($_key, $_cache)) {
            if (false === ($_allowedVerbs = $this->_checkOrigin($_originParts, [$_requestUri], $_isStar))) {
                $this->_logger->error('unauthorized origin rejected > Source: ' . $_requestUri . ' > Origin: ' . $_originUri);

                /**
                 * No sir, I didn't like it.
                 *
                 * @link http://www.youtube.com/watch?v=VRaoHi_xcWk
                 */
                header('HTTP/1.1 403 Forbidden');

                exit(0);
            }

            $_cache[$_key] = [
                'origin-uri'    => $_originUri,
                'allowed-verbs' => $_allowedVerbs,
                'cors-headers'  => [],
            ];
        } else {
            $_originUri = IfSet::get($_cache[$_key], 'origin-uri');
            $_allowedVerbs = IfSet::get($_cache[$_key], 'allowed-verbs');
        }

        //  Rebuild headers
        $_headers = [
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Headers'     => implode(',', $this->_headers),
            'Access-Control-Allow-Methods'     => implode(', ', $_allowedVerbs),
            'Access-Control-Allow-Origin'      => $_isStar ? static::ALLOW_ALL : $_originUri,
            'Access-Control-Max-Age'           => static::DEFAULT_MAX_AGE,
        ];

        //	Store in cache...
        $_cache[$_key] = [
            'origin-uri'    => $_originUri,
            'allowed-verbs' => $_allowedVerbs,
            'cors-headers'  => $_headers,
        ];

        $this->_cache && $this->_cache->save($this->_id . '.headers', $_cache);

        if ($returnHeaders) {
            return $_headers;
        }

        //  Send all the headers
        if ($sendHeaders) {
            $_out = null;

            foreach ($_headers as $_key => $_value) {
                $_key = implode('-', array_map('ucfirst', explode('-', $_key)));

                header($_key . ': ' . $_value);

                $_out .= $_key . ': ' . $_value . PHP_EOL;
            }

            $this->_logger->debug('CORS: headers sent' . PHP_EOL . '*=== Headers Start ===*' . PHP_EOL . $_out . '*=== Headers End ===*' . PHP_EOL);
        }

        return true;
    }

    /**
     * @param string|array $origin     The parse_url value of origin
     * @param array        $additional Additional origin(s) to allow
     * @param bool         $isStar     Set to true if the allowed origin is "*"
     *
     * @return bool|array false if not allowed, otherwise array of verbs allowed
     */
    protected function _checkOrigin($origin, array $additional = [], &$isStar = false)
    {
        $_checklist = array_merge($this->_whitelist, $additional);

        foreach ($_checklist as $_hostInfo) {
            //  Always start with defaults
            $_allowedVerbs = $this->_verbs;
            $_whiteGuy = $_hostInfo;

            if (is_array($_hostInfo)) {
                //	If is_enabled prop not there, assuming enabled.
                if (!Scalar::boolval(IfSet::get($_hostInfo, 'is_enabled', true))) {
                    continue;
                }

                if (null === ($_whiteGuy = IfSet::get($_hostInfo, 'host'))) {
                    $this->_logger->error('whitelist entry missing "host" parameter');

                    continue;
                }

                if (isset($_hostInfo['verbs'])) {
                    if (!in_array(Verbs::OPTIONS, $_hostInfo['verbs'])) {
                        // add OPTION to allowed list
                        $_hostInfo['verbs'][] = Verbs::OPTIONS;
                    }

                    $_allowedVerbs = $_hostInfo['verbs'];
                }
            }

            //	All allowed?
            if (static::ALLOW_ALL == $_whiteGuy) {
                $isStar = true;

                return $_allowedVerbs;
            }

            if (false === ($_whiteParts = Uri::parse($_whiteGuy))) {
                $this->_logger->error('unable to parse "' . $_whiteGuy . '" whitelist entry');

                continue;
            }

            $this->_logger->debug('whitelist "' . $_whiteGuy . '" > parts: ' . print_r($_whiteParts, true));

            //	Check for un-parsed origin, 'null' sent when testing js files locally
            if (is_array($origin) && Uri::compare($origin, $_whiteParts)) {
                //	This origin is on the whitelist
                return $_allowedVerbs;
            }
        }

        return false;
    }

    /**
     * Retrieve/Create the CORS configuration for this request
     */
    protected function _loadConfig()
    {
        //  Cached? Pull it out
        if ($this->_cache && false !== ($_list = $this->_cache->fetch($this->_id))) {
            $_whitelist = $_list;
        } else {
            //  Empty whitelist...
            $_whitelist = [];

            //	Get CORS data from config file
            $_configFile = $this->_configPath . static::DEFAULT_CONFIG_FILE;

            if (false !== ($_config = Includer::includeIfExists($_configFile))) {
                if (false === ($_whitelist = json_decode($_config, true)) || JSON_ERROR_NONE != json_last_error()) {
                    throw new \RuntimeException('The CORS configuration file is corrupt. Cannot continue.');
                }

                $this->_logger->debug('CORS: configuration loaded. Whitelist = ' . print_r($_whitelist, true));
            }
        }

        //  Cache/re-cache if we can
        $this->_cache && $this->_cache->save(static::WHITELIST_KEY,
            $_whitelist,
            static::CACHE_TTL) && $this->_logger->debug('whitelist cached');

        //  Set member
        $this->_whitelist = $_whitelist;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return Cors
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @param Request $request
     *
     * @return Cors
     */
    public function setRequest($request)
    {
        $this->_request = $request;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * @return array
     */
    public function getVerbs()
    {
        return $this->_verbs;
    }

    /**
     * @param array $verbs
     *
     * @return Cors
     */
    public function setVerbs($verbs)
    {
        $this->_verbs = $verbs;

        return $this;
    }

    /**
     * @return array
     */
    public function getWhitelist()
    {
        return $this->_whitelist;
    }

    /**
     * @param array $whitelist
     *
     * @return Cors
     */
    public function setWhitelist($whitelist)
    {
        $this->_whitelist = $whitelist;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigFile()
    {
        return $this->_configFile;
    }

    /**
     * @param string $configFile
     *
     * @return Cors
     */
    public function setConfigFile($configFile)
    {
        $this->_configFile = $configFile;

        return $this;
    }

    /**
     * @return string
     */
    public function getRequestOrigin()
    {
        return $this->_requestOrigin;
    }

    /**
     * @return string
     */
    public function getRequestVerb()
    {
        return $this->_requestVerb;
    }

    /**
     * @return boolean
     */
    public function isCorsRequest()
    {
        return $this->_corsRequest;
    }
}
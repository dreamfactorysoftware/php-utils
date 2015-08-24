<?php namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Utility\Enums\Verbs;

/**
 * Simple class to encapsulate a CORS configuration entry
 */
class CorsEntry
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string Indicates no restrictions when used as the host
     */
    const WIDE_OPEN = '*';

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The host of this entry
     */
    protected $_host;
    /**
     * @type string The port of this entry
     */
    protected $_port;
    /**
     * @type string The scheme of this entry
     */
    protected $_scheme;
    /**
     * @type array The allowed verbs for this entry
     */
    protected $_allowedVerbs;
    /**
     * @type bool
     */
    protected $_enabled = true;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param string $host         The host of the entry, or "*" for all
     * @param int    $port         The port of the entry
     * @param string $scheme       The scheme of the entry
     * @param array  $allowedVerbs The verbs allowed for the entry. Defaults to all verbs being allowed
     */
    public function __construct($host = null, $port = null, $scheme = null, $allowedVerbs = null)
    {
        $this->_host = strtolower($host ?: IfSet::get($_SERVER, 'HTTP_HOST'));

        if (static::WIDE_OPEN != $this->_host) {
            $this->_port = $port ?: IfSet::get($_SERVER, 'SERVER_PORT');
            $this->_scheme = $scheme ?: 'http' . (IfSet::getBool($_SERVER, 'HTTPS') ? 's' : null);

            //  Ignore standard ports
            'https' == $this->_scheme && 443 == $this->_port && $this->_port = null;
            'http' == $this->_scheme && 80 == $this->_port && $this->_port = null;
        }

        $this->_allowedVerbs = $allowedVerbs
            ?: [
                Verbs::GET,
                Verbs::POST,
                Verbs::PUT,
                Verbs::DELETE,
                Verbs::PATCH,
                Verbs::MERGE,
                Verbs::COPY,
                Verbs::OPTIONS,
            ];
    }

    /**
     * @return string The complete uri of this entry (i.e. scheme://host:port). If port is standard or not-specified, it is not appended.
     */
    public function getUri()
    {
        return $this->_scheme . '://' . $this->_host . ($this->_port ? ':' . $this->_port : null);
    }

    /**
     * Given a verb, check if this host is allowed to use it
     *
     * @param string $verb
     *
     * @return bool
     */
    public function isVerbAllowed($verb)
    {
        $verb = strtoupper($verb);

        if (!Verbs::contains($verb)) {
            throw new \InvalidArgumentException('The verb "' . $verb . '" is not valid.');
        }

        return in_array($verb, $this->_allowedVerbs);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * @param string $host
     *
     * @return CorsEntry
     */
    public function setHost($host)
    {
        $this->_host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * @param string $port
     *
     * @return CorsEntry
     */
    public function setPort($port)
    {
        $this->_port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->_scheme;
    }

    /**
     * @param string $scheme
     *
     * @return CorsEntry
     */
    public function setScheme($scheme)
    {
        $this->_scheme = $scheme;

        return $this;
    }

    /**
     * @return array
     */
    public function getAllowedVerbs()
    {
        return $this->_allowedVerbs;
    }

    /**
     * @param array $allowedVerbs
     *
     * @return CorsEntry
     */
    public function setAllowedVerbs($allowedVerbs)
    {
        $this->_allowedVerbs = $allowedVerbs;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->_enabled;
    }

    /**
     * @param boolean $enabled
     *
     * @return CorsEntry
     */
    public function setEnabled($enabled)
    {
        $this->_enabled = $enabled;

        return $this;
    }

}
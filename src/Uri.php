<?php namespace DreamFactory\Library\Utility;

class Uri
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param string $uri       The uri to parse
     * @param bool   $normalize If true, uri will be normalized to a string
     *
     * @return array|string
     */
    public static function parse($uri, $normalize = false)
    {
        //  Don't parse empty uris
        if (empty($uri)) {
            return true;
        }

        //  Let PHP have a whack at it
        $_parts = parse_url($uri);

        //  Unparsable or missing host or path, we bail
        if (false === $_parts || !(isset($_parts['host']) || isset($_parts['path']))) {
            return false;
        }

        $_scheme = IfSet::get($_parts, 'scheme', Scalar::boolVal(IfSet::get($_SERVER, 'HTTPS')) ? 'https' : 'http');

        $_port = IfSet::get($_parts, 'port');
        $_host = IfSet::get($_parts, 'host');
        $_path = IfSet::get($_parts, 'path');

        //  Set ports to defaults for scheme if empty
        if (empty($_port)) {
            $_port = null;

            switch ($_parts['scheme']) {
                case 'http':
                    $_port = 80;
                    break;

                case 'https':
                    $_port = 443;
                    break;
            }
        }

        //  If standard port 80 or 443 and there is no port in uri, clear from parse...
        if (!empty($_port)) {
            if (empty($_host) || (($_port == 80 || $_port == 443) && false === strpos($uri, ':' . $_port))) {
                $_port = null;
            }
        }

        if (!empty($_path) && empty($_host)) {
            //	Special case, handle this generically later
            if ('null' == $_path) {
                return 'null';
            }

            $_host = $_path;
            $_path = null;
        }

        $_uri = [
            'scheme' => $_scheme,
            'host'   => $_host,
            'port'   => $_port,
        ];

        return $normalize ? static::normalize($_uri) : $_uri;
    }

    /**
     * @param array $parts Parts of an uri
     *
     * @return string
     */
    public static function normalize(array $parts)
    {
        $_uri = $parts['scheme'] . '://' . $parts['host'];

        if (!empty($parts['port'])) {
            $_uri .= ':' . $parts['port'];
        }

        return trim($_uri);
    }

    /**
     * @param array $first  uri to compare
     * @param array $second uri to compare
     *
     * @return bool true if they are the same, false otherwise
     */
    public static function compare(array $first, array $second)
    {
        $_diff = array_diff_assoc($first, $second);

        return empty($_diff);
    }

    /**
     * Concatenate $parts into $separator delimited, trimmed, clean string.
     *
     * @param string|array $parts     The part or parts to join
     * @param bool         $leading   If true (default), a leading $separator will be added
     * @param string       $separator The delimiter to use
     *
     * @return null|string
     */
    public static function segment($parts = [], $leading = true, $separator = '/')
    {
        return Disk::segment($parts, $leading, $separator);
    }
}
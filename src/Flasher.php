<?php namespace DreamFactory\Library\Utility;

use Illuminate\Support\Facades\Session;

/**
 * Helpers for UI session flash messages
 */
class Flasher
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The prefix for flash keys
     */
    protected static $prefix;
    /**
     * @type string The separator between key segments
     */
    protected static $separator = '.';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param string      $value
     * @param bool|true   $success
     * @param string|null $key If specified, use as flash key
     */
    public static function flash( $value, $success = true, $key = null )
    {
        static::session()->flash(
            $key ?: static::buildFlashKey( $success ),
            $value
        );
    }

    /**
     * Flashes a message ONLY if the key is empty
     *
     * @param string      $value
     * @param bool        $success
     * @param string|null $key If specified, use as flash key
     */
    public static function flashIf( $value, $success = true, $key = null )
    {
        $_key = $key ?: static::buildFlashKey( $success );
        !static::session()->has( $_key ) && static::flash( $value, $success, $_key );
    }

    /**
     * Clear the flash
     *
     * @param bool|null $success Which key to clear, null for both (default)
     */
    public static function forget( $success = null )
    {
        $_keys =
            null === $success ? [static::buildFlashKey( true ), static::buildFlashKey( false )]
                : [static::buildFlashKey( $success )];

        foreach ( $_keys as $_key )
        {
            static::session()->forget( $_key );
        }
    }

    /**
     * @param string|array|null $key
     * @param mixed|null        $default
     *
     * @return Session|mixed
     */
    protected static function session( $key = null, $default = null )
    {
        return session( $key, $default );
    }

    /**
     * Constructs the complete flash key
     *
     * @param bool|true $success
     *
     * @return string
     */
    protected static function buildFlashKey( $success = true )
    {
        return ( static::$prefix ? static::$prefix . static::$separator : null ) . ( $success ? 'success' : 'failure' );
    }

    /**
     * @return string
     */
    public static function getPrefix()
    {
        return static::$prefix;
    }

    /**
     * @param string $prefix
     */
    public static function setPrefix( $prefix )
    {
        static::$prefix = $prefix;
    }

    /**
     * @return string
     */
    public static function getSeparator()
    {
        return static::$separator;
    }

    /**
     * @param string $separator
     */
    public static function setSeparator( $separator )
    {
        static::$separator = $separator;
    }
}
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
    public static function set($value, $success = true, $key = null)
    {
        static::session()->flash($key ?: static::buildFlashKey($success),
            $value);
    }

    /**
     * @param string|null $key If specified, use as flash key
     * @param mixed|null  $default
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return static::session()->get($key, $default);
    }

    /**
     * Flashes a message ONLY if the key is empty
     *
     * @param string      $value
     * @param bool        $success
     * @param string|null $key If specified, use as flash key
     */
    public static function setIf($value, $success = true, $key = null)
    {
        $_key = $key ?: static::buildFlashKey($success);
        !static::session()->has($_key) && static::set($value, $success, $_key);
    }

    /**
     * Clear the flash
     *
     * @param bool|null $success Which key to clear, null for both (default)
     */
    public static function forget($success = null)
    {
        $_keys =
            null === $success ? [static::buildFlashKey(true), static::buildFlashKey(false)]
                : [static::buildFlashKey($success)];

        foreach ($_keys as $_key) {
            static::session()->forget($_key);
        }
    }

    /**
     * A one-time use "alert", or Public Service Announcement (PSA)
     *
     * @param string      $title   The title within the div/container
     * @param null        $content The content within the div/container
     * @param string      $context The context of the div/container
     * @param string|null $id      Optional "id" attribute for the created alert div/container
     * @param bool|false  $hidden  If true, the container is hidden. Ignored if $id === null
     *
     * @return string
     */
    public static function psa($title, $content = null, $context = 'alert-info', $id = null, $hidden = false)
    {
        $id && $hidden && $context .= ' hide';

        return view('app.templates.psa',
            [
                'alert_id' => $id,
                'context'  => $context,
                'title'    => $title,
                'content'  => $content,
            ])->render();
    }

    /**
     * Checks for a success/failure flash message and renders the "alert" HTML.
     * Uses values from /resources/lang/en/dashboard.php
     *
     * @param bool $errorsFixed If true, the "alert-fixed" class is added to the alert container
     *
     * @return null|string The rendered HTML or null
     */
    public static function getAlert($errorsFixed = true)
    {
        $_data = [];

        $_keys = [
            'failure' => static::buildFlashKey(false),
            'success' => static::buildFlashKey(true),
        ];

        foreach ($_keys as $_success => $_key) {
            if (static::session()->has($_key)) {
                $_data = [
                    'flash'   => static::get($_key),
                    'title'   => \Lang::get($_key . '.title') ?: ucwords($_success),
                    'context' => config('dashboard.alerts.' . $_success . '.context', 'alert-info') .
                        ($errorsFixed ? ' alert-fixed' : null),
                ];

                break;
            }
        }

        return empty($_data) ? null : view('app.templates.flash-alert', $_data)->render();
    }

    /**
     * @param string|array|null $key
     * @param mixed|null        $default
     *
     * @return Session|mixed
     */
    protected static function session($key = null, $default = null)
    {
        return session($key, $default);
    }

    /**
     * Constructs the complete flash key
     *
     * @param bool|true $success
     *
     * @return string
     */
    protected static function buildFlashKey($success = true)
    {
        return (static::$prefix ? static::$prefix . static::$separator : null) . ($success ? 'success' : 'failure');
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
     *
     * @return string The value before being set (the "old" value)
     */
    public static function setPrefix($prefix)
    {
        $_lastValue = static::$prefix;
        static::$prefix = $prefix;

        return $_lastValue;
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
     *
     * @return string The value before being set (the "old" value)
     */
    public static function setSeparator($separator)
    {
        $_lastValue = static::$separator;
        static::$separator = $separator;

        return $_lastValue;
    }
}
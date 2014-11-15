<?php
namespace DreamFactory\Library\Utility;

class IfSet
{
    /**
     * @param array|\ArrayObject $target       Target to grab $key from
     * @param string             $key          Index into target to retrieve
     * @param mixed              $defaultValue Value returned if $key is not in $target
     *
     * @return mixed
     */
    public static function get( array $target, $key, $defaultValue = null )
    {
        return
            is_array( $target )
                ? ( array_key_exists( $key, $target ) ? $target[$key] : $defaultValue )
                : $defaultValue;
    }

    /**
     * @param array|\ArrayObject $target Target to check
     * @param string             $key    Key to check
     *
     * @return bool
     */
    public static function has( array $target, $key )
    {
        return
            is_array( $target ) && array_key_exists( $key, $target );
    }

    /**
     * @param array|\ArrayObject $target Target to grab $key from
     * @param string             $key    Index into target to retrieve
     * @param mixed              $value  The value to set
     */
    public static function set( array $target, $key, $value = null )
    {
        is_array( $target ) && $target[$key] = $value;
    }

    /**
     * @param array|\ArrayObject $target       Target to grab $key from
     * @param string             $key          Index into target to retrieve
     * @param bool               $defaultValue Value returned if $key is not in $target
     *
     * @return mixed
     */
    public static function getBool( array $target, $key, $defaultValue = false )
    {
        return static::boolval( static::get( $target, $key, $defaultValue ) );
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public static function boolval( $value )
    {
        if ( \is_bool( $value ) )
        {
            return $value;
        }

        $_value = \strtolower( (string)$value );

        //	FILTER_VALIDATE_BOOLEAN doesn't catch 'Y' or 'N', so convert to full words...
        if ( 'y' == $_value )
        {
            $_value = 'yes';
        }
        elseif ( 'n' == $_value )
        {
            $_value = 'no';
        }

        return \filter_var( $_value, FILTER_VALIDATE_BOOLEAN );
    }

}
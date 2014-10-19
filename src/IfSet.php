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
        if ( array_key_exists( $key, $target ) )
        {
            return $target[$key];
        }

        return $defaultValue;
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
        if ( array_key_exists( $key, $target ) )
        {
            return static::boolval( $target[$key] );
        }

        return static::boolval( $defaultValue );
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
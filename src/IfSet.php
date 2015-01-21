<?php
namespace DreamFactory\Library\Utility;

class IfSet
{
    /**
     * @param array|\ArrayObject $target            Target to grab $key from
     * @param string             $key               Index into target to retrieve
     * @param mixed              $defaultValue      Value returned if $key is not in $target
     * @param bool               $emptyStringIsNull If true, and the result is an empty string (''), NULL is returned
     *
     * @return mixed
     */
    public static function get( array $target, $key, $defaultValue = null, $emptyStringIsNull = false )
    {
        $_result = is_array( $target ) ? ( array_key_exists( $key, $target ) ? $target[$key] : $defaultValue ) : $defaultValue;

        return $emptyStringIsNull && '' === $_result ? null : $_result;
    }

    /**
     * @param array|\ArrayObject $target       Target to grab $key from
     * @param string             $key          Index into target to retrieve
     * @param bool               $defaultValue Value returned if $key is not in $target
     *
     * @return bool
     */
    public static function getBool( array $target, $key, $defaultValue = false )
    {
        return static::boolval( static::get( $target, $key, $defaultValue ) );
    }

    /**
     * @param array|\ArrayAccess|object $options
     * @param string                    $key
     * @param string                    $subKey
     * @param mixed                     $defaultValue      Only applies to target value
     * @param bool                      $emptyStringIsNull If true, empty() values will always return as NULL
     *
     * @return mixed
     */
    public static function getDeep( $options = array(), $key, $subKey, $defaultValue = null, $emptyStringIsNull = false )
    {
        $_deep = static::get( $options, $key, array(), $emptyStringIsNull );

        return static::get( $_deep, $subKey, $defaultValue, $emptyStringIsNull );
    }

    /**
     * @param array|\ArrayAccess|object $options
     * @param string                    $key
     * @param string                    $subKey
     * @param mixed                     $defaultValue Only applies to target value
     *
     * @return bool
     */
    public static function getDeepBool( $options = array(), $key, $subKey, $defaultValue = null )
    {
        $_deep = static::get( $options, $key, array(), $defaultValue );

        return static::getBool( $_deep, $subKey, $defaultValue );
    }

    /**
     * @param array|\ArrayObject $target Target to check
     * @param string             $key    Key to check
     *
     * @return bool
     */
    public static function has( array $target, $key )
    {
        return is_array( $target ) && array_key_exists( $key, $target );
    }

    /**
     * @param array|\ArrayObject $target Target to check
     * @param string             $key    Key to check
     * @param string             $subKey Subkey to check
     *
     * @return bool
     */
    public static function hasDeep( array $target, $key, $subKey )
    {
        return static::has( $target, $key ) && static::has( $target[$key], $subKey );
    }

    /**
     * @param array|\ArrayObject $target Target to set $key in
     * @param string             $key    Index into target to retrieve
     * @param mixed              $value  The value to set
     */
    public static function set( array $target, $key, $value = null )
    {
        is_array( $target ) && $target[$key] = $value;
    }

    /**
     * @param array|\ArrayObject $target Target to set $key in
     * @param string             $key    Index into target to retrieve
     * @param mixed              $value  The value to set
     */
    public static function add( array $target, $key, $value = null )
    {
        //  If a value exists, convert to array and add value
        if ( null !== ( $_current = static::get( $target, $key ) ) )
        {
            static::set( $target, $key, array($_current, $value) );

            return;
        }

        //  Otherwise, just set key
        static::set( $target, $key, $value );
    }

    /**
     * @param array|\ArrayObject $target Target to set $key in
     * @param string             $key    Index into target to retrieve
     *
     * @return bool True if key existed and was removed
     */
    public static function remove( array $target, $key )
    {
        if ( static::has( $target, $key ) )
        {
            unset( $target[$key] );

            return true;
        }

        return false;
    }

    /**
     * @param array|\ArrayObject $target Target to set $key in
     * @param string             $key    Index into target to set
     * @param string             $subKey The subkey to set
     * @param mixed              $value  The value to set
     */
    public static function setDeep( array $target, $key, $subKey, $value = null )
    {
        !array_key_exists( $target, $key ) && $target[$key] = array();

        //  Not an array, bail...
        if ( !is_array( $target[$key] ) )
        {
            throw new \InvalidArgumentException( 'The object at $target[$key] must be an array.' );
        }

        static::set( $target[$key], $subKey, $value );
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
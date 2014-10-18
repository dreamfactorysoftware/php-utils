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
}
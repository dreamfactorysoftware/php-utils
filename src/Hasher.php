<?php namespace DreamFactory\Library\Utility;

class Hasher
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param mixed  $value     The value to hash
     * @param string $algorithm The algorithm to use for hashing. Defaults to "sha256"
     * @param bool   $raw       If true, the raw binary value is returned
     *
     * @return mixed
     */
    public static function make($value, $algorithm = 'sha256', $raw = false)
    {
        return hash($algorithm, $value, $raw);
    }
}

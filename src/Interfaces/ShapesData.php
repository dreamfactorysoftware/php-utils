<?php namespace DreamFactory\Library\Utility\Interfaces;

/**
 * An object that can reshape data
 */
interface ShapesData
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Transforms an array of data into a new shape
     *
     * @param array $source  The source data
     * @param array $options Any options to pass through to the shaping mechanism
     *
     * @return mixed
     */
    public static function transform(array $source, $options = []);
}
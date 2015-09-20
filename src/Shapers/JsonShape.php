<?php namespace DreamFactory\Library\Utility\Shapers;

use DreamFactory\Library\Utility\Interfaces\ShapesData;
use DreamFactory\Library\Utility\Json;

class JsonShape implements ShapesData
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
    public static function transform(array $source, $options = [])
    {
        return Json::encode($source, data_get($options, 'options'), data_get($options, 'depth', 512));
    }
}

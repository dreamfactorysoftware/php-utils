<?php namespace DreamFactory\Library\Utility;

/**
 * Encode/decode JSON in a standard way across the entire DF codebase
 */
class Json
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int
     */
    const JSON_UNESCAPED_SLASHES = 64;
    /**
     * @type int
     */
    const JSON_PRETTY_PRINT = 128;
    /**
     * @type int
     */
    const JSON_UNESCAPED_UNICODE = 256;
    /**
     * @type int The default options for json_encode. This value is (JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)
     */
    const DEFAULT_JSON_ENCODE_OPTIONS = 320;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * JSON encodes data
     *
     * @param  mixed $data    Data to encode
     * @param int    $options Options for json_encode. Default is (JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
     * @param int    $depth   The maximum depth to recurse
     *
     * @return string Encoded json
     */
    public static function encode($data, $options = null, $depth = 512)
    {
        $options = $options ?: static::DEFAULT_JSON_ENCODE_OPTIONS;

        if (false === ($_json = json_encode($data, $options, $depth)) || JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('The data could not be encoded: ' . json_last_error_msg());
        }

        return $_json;
    }

    /**
     * Decodes a JSON string
     *
     * @param string $json    The data to decode
     * @param bool   $asArray If true, data is returned as an array vs. a \stdClass
     * @param int    $depth   The maximum depth to recurse
     * @param int    $options Any json_decode() options
     *
     * @return mixed
     */
    public static function decode($json, $asArray = true, $depth = 512, $options = 0)
    {
        if (false === ($_data = json_decode($json, $asArray, $depth, $options)) ||
            JSON_ERROR_NONE != json_last_error()
        ) {
            throw new \InvalidArgumentException('The data could not be decoded: ' . json_last_error_msg());
        }

        return $_data;
    }
}
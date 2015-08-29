<?php namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Utility\Exceptions\FileException;
use DreamFactory\Library\Utility\Exceptions\FileSystemException;

/**
 * Reads/writes a json file
 */
class JsonFile extends Json
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int The number of times to retry write operations (default is 3)
     */
    const STORAGE_OPERATION_RETRY_COUNT = 3;
    /**
     * @type int The number of times to retry write operations (default is 5s)
     */
    const STORAGE_OPERATION_RETRY_DELAY = 500000;

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type bool If true, a copy of files to be overwritten will be made
     */
    protected static $makeBackups = false;
    /**
     * @type string The absolute path of our JSON file.
     */
    protected $filePath;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param string       $filePath    The absolute path of the file, including the name.
     * @param bool         $makeBackups If true, a copy of files to be overwritten will be made
     * @param array|object $contents    The contents to write to the file if being created
     */
    public function __construct($filePath = null, $makeBackups = true, $contents = [])
    {
        static::$makeBackups = $makeBackups;
        static::ensureFileExists($filePath, $contents) && $this->filePath = $filePath;
    }

    /**
     * Reads the file and returns the contents
     *
     * @param bool $decoded If true (the default), the read data is decoded
     * @param bool $asArray If true, the default, data is return in an array. Otherwise it comes back as a \stdClass
     * @param int  $depth   The maximum recursion depth
     * @param int  $options Any json_decode options
     *
     * @return array|object
     */
    public function read($decoded = true, $asArray = true, $depth = 512, $options = 0)
    {
        if (!file_exists($this->filePath)) {
            throw new FileSystemException('The file "' . $this->filePath . '" does not exist.');
        }

        if (!$decoded) {
            return file_get_contents($this->filePath);
        }

        return static::decodeFile($this->filePath, $asArray, $depth, $options);
    }

    /**
     * Writes the file
     *
     * @param array|object $data       The unencoded data to store. If empty, the static::toArray() method result is
     *                                 written
     * @param int          $options    Options for json_encode. Default is static::DEFAULT_JSON_ENCODE_OPTIONS
     * @param int          $retries    The number of times to retry the write.
     * @param float|int    $retryDelay The number of microseconds (100000 = 1s) to wait between retries
     *
     * @return bool
     */
    public function write($data = [], $options = null, $retries = self::STORAGE_OPERATION_RETRY_COUNT, $retryDelay = self::STORAGE_OPERATION_RETRY_DELAY)
    {
        return static::encodeFile($this->filePath, $data ?: [], $options, $retries, $retryDelay);
    }

    /**
     * Makes sure the file passed it exists. Create default config and saves otherwise.
     *
     * @param string       $filePath        The absolute path of the file, including the name.
     * @param array|object $defaultContents The contents to write to the file if being created
     * @param bool         $checkOnly       If true, only existence is checked. No exceptions will be thrown
     *
     * @return string
     */
    public function ensureFileExists($filePath, $defaultContents = null, $checkOnly = false)
    {
        if (!Disk::ensurePath($_path = dirname($filePath))) {
            if ($checkOnly) {
                return false;
            }

            throw new \InvalidArgumentException('The path "' . $_path . '" is not writable.');
        }

        //  Create a blank file if one does not exists
        !file_exists($filePath) && static::encodeFile($filePath, $defaultContents ?: []);

        //  Exists
        return is_file($filePath);
    }

    /**
     * Encodes and writes contents to file. If $data is not a JSON string, it will be converted to one automatically
     *
     * @param string $file       The absolute path to the destination file
     * @param mixed  $data       The data to encode and write
     * @param int    $options    The JSON encoding options
     * @param int    $depth      The maximum depth to recurse
     * @param int    $retries    The number of times to retry writing the file
     * @param int    $retryDelay The number of microseconds (not milliseconds)
     *
     * @return bool
     */
    public static function encodeFile($file, $data, $options = null, $depth = 512, $retries = self::STORAGE_OPERATION_RETRY_COUNT, $retryDelay = self::STORAGE_OPERATION_RETRY_DELAY)
    {
        if (!Disk::ensurePath($_path = dirname($file)) || !is_writeable($_path)) {
            throw new \InvalidArgumentException('The path "' . $_path . '" is not writable.');
        }

        file_exists($file) && static::backupExistingFile($file);

        if (!is_string($data)) {
            if (false === ($_json = static::encode($data, $options, $depth)) || JSON_ERROR_NONE != json_last_error()) {
                throw new \InvalidArgumentException('The $data cannot be converted to JSON.');
            }

            $data = $_json;
        }

        while ($retries--) {
            try {
                if (false === file_put_contents($file, $data)) {
                    throw new FileException('Unable to write data to file "' .
                        $file .
                        '" after ' .
                        $retries .
                        ' attempt(s).');
                }

                break;
            } catch (FileException $_ex) {
                if ($retries) {
                    usleep($retryDelay);
                    continue;
                }

                throw $_ex;
            }
        }

        return true;
    }

    /**
     * Reads a file and decodes the contents
     *
     * @param string $file The absolute path to the file to decode
     * @param bool   $asArray
     * @param int    $depth
     * @param int    $options
     *
     * @return mixed
     */
    public static function decodeFile($file, $asArray = true, $depth = 512, $options = 0)
    {
        if (Disk::ensurePath($_path = dirname($file)) && file_exists($file) && is_readable($file)) {
            return static::decode(file_get_contents($file), $asArray, $depth, $options);
        }

        throw new \InvalidArgumentException('"' . $file . '" cannot be read.');
    }

    /**
     * @param string $file Absolute path to our file
     *
     * @return bool|int
     */
    protected static function backupExistingFile($file)
    {
        static $_template = '{file}.{date}.save';

        if (!static::$makeBackups || !file_exists($file)) {
            return true;
        }

        if (!Disk::ensurePath($_path = dirname($file))) {
            throw new \RuntimeException('Unable to create file "' . $file . '"');
        }

        return file_put_contents(str_replace(['{file}', '{date}'], [basename($file), date('YmdHiS')], $_template),
            file_get_contents($file));
    }

    /**
     * @return boolean
     */
    public static function getMakeBackups()
    {
        return static::$makeBackups;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }
}
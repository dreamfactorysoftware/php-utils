<?php
namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Utility\Exceptions\FileException;
use DreamFactory\Library\Utility\Exceptions\FileSystemException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Reads/writes a json file
 * Can be used statically as well for various functionality
 */
class JsonFile implements Arrayable, Jsonable
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
     * @type int The default options for json_encode. This value is (JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE)
     */
    const DEFAULT_JSON_ENCODE_OPTIONS = 448;
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
    protected static $_makeBackups = false;
    /**
     * @type string The absolute path of our JSON file.
     */
    protected $_filePath;
    /**
     * @type array A map of properties to their JSON equivalents and vice-versa
     */
    protected $_propertyMap;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Construct
     *
     * @param string       $filePath        The absolute path of the file, including the name.
     * @param array|object $defaultContents The contents to write to the file if being created
     * @param bool         $makeBackups     If true, a copy of files to be overwritten will be made
     */
    public function __construct( $filePath = null, $defaultContents = null, $makeBackups = true )
    {
        static::$_makeBackups = $makeBackups;
        static::ensureFileExists( $filePath, $defaultContents ) && $this->_filePath = $filePath;
    }

    /**
     * Reads the file and returns the contents
     *
     * @param bool $decoded  If true (the default), the read data is decoded
     * @param bool $asArray  If true, the default, data is return in an array. Otherwise it comes back as a \stdClass
     * @param int  $depth    The maximum recursion depth
     * @param int  $options  Any json_decode options
     * @param bool $populate If true, any read data will be placed into matching member variables
     *
     * @return array|object
     */
    public function read( $decoded = true, $asArray = true, $depth = 512, $options = 0, $populate = false )
    {
        if ( !static::ensureFileExists( $this->_filePath ) )
        {
            throw new FileSystemException( 'The file "' . $this->_filePath . '" does not exist.' );
        }

        $_data = static::decodeFile( $this->_filePath, $asArray, $depth, $options );

        $populate && $this->_populateProperties( $_data );

        return $decoded ? $_data : static::encode( $_data );
    }

    /**
     * Writes the file
     *
     * @param array|object $data       The unencoded data to store. If empty, the static::toArray() method result is written
     * @param int          $options    Options for json_encode. Default is static::DEFAULT_JSON_ENCODE_OPTIONS
     * @param int          $retries    The number of times to retry the write.
     * @param float|int    $retryDelay The number of microseconds (100000 = 1s) to wait between retries
     *
     * @throws FileSystemException
     * @throws \Exception
     */
    public function write( $data = null, $options = self::DEFAULT_JSON_ENCODE_OPTIONS, $retries = self::STORAGE_OPERATION_RETRY_COUNT, $retryDelay = self::STORAGE_OPERATION_RETRY_DELAY )
    {
        static::encodeFile( $this->_filePath, $data ?: $this->toArray(), $options, $retries, $retryDelay );
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
    public function ensureFileExists( $filePath, $defaultContents = null, $checkOnly = false )
    {
        if ( !FileSystem::ensurePath( $_path = dirname( $filePath ) ) )
        {
            if ( $checkOnly )
            {
                return false;
            }

            throw new \InvalidArgumentException( 'The path "' . $_path . '" is not writeable.' );
        }

        //  Create a blank file if one does not exists
        !file_exists( $filePath ) && static::encodeFile( $filePath, $defaultContents ?: $this->toArray() );

        //  Exists
        return is_file( $filePath );
    }

    /**
     * JSON encodes data
     *
     * @param  mixed $data    Data to encode
     * @param int    $options Options for json_encode. Default is static::DEFAULT_JSON_ENCODE_OPTIONS
     * @param int    $depth   The maximum depth to recurse
     *
     * @return string Encoded json
     */
    public static function encode( $data, $options = self::DEFAULT_JSON_ENCODE_OPTIONS, $depth = 512 )
    {
        if ( false === ( $_json = json_encode( $data, $options, $depth ) ) || JSON_ERROR_NONE !== json_last_error() )
        {
            throw new \InvalidArgumentException( 'The data could not be encoded: ' . json_last_error_msg() );
        }

        return $_json . ( $options & static::JSON_PRETTY_PRINT ? PHP_EOL : null );
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
     */
    public static function encodeFile( $file, $data, $options = self::JSON_UNESCAPED_SLASHES, $depth = 512, $retries = self::STORAGE_OPERATION_RETRY_COUNT, $retryDelay = self::STORAGE_OPERATION_RETRY_DELAY )
    {
        if ( !FileSystem::ensurePath( $_path = dirname( $file ) ) || !is_writeable( $_path ) )
        {
            throw new \InvalidArgumentException( 'The path "' . $_path . '" is not writeable.' );
        }

        file_exists( $file ) && static::_backupExistingFile( $file );

        while ( $retries-- )
        {
            try
            {
                if ( false === file_put_contents( $file, !is_string( $data ) ? static::encode( $data, $options, $depth ) : $data ) )
                {
                    throw new FileException( 'Unable to write data to file "' . $file . '" after ' . $retries . ' attempt(s).' );
                }

                break;
            }
            catch ( FileException $_ex )
            {
                if ( $retries )
                {
                    usleep( $retryDelay );
                    continue;
                }

                throw $_ex;
            }
        }
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
    public static function decode( $json, $asArray = true, $depth = 512, $options = self::DEFAULT_JSON_ENCODE_OPTIONS )
    {
        if ( false === ( $_data = json_decode( $json, $asArray, $depth, $options ) ) || JSON_ERROR_NONE != json_last_error() )
        {
            throw new \InvalidArgumentException( 'The data could not be decoded: ' . json_last_error_msg() );
        }

        return $_data;
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
    public static function decodeFile( $file, $asArray = true, $depth = 512, $options = self::DEFAULT_JSON_ENCODE_OPTIONS )
    {
        if ( FileSystem::ensurePath( $_path = dirname( $file ) ) && file_exists( $file ) && is_readable( $file ) )
        {
            return static::decode( file_get_contents( $file ), $asArray, $depth, $options );
        }

        throw new \InvalidArgumentException( 'The file "' . $file . '" does not exist or cannot be read.' );
    }

    /**
     * @param string $file Absolute path to our file
     *
     * @return bool|int
     */
    protected static function _backupExistingFile( $file )
    {
        static $_template = '{file}.{date}.save';

        if ( !static::$_makeBackups || !file_exists( $file ) )
        {
            return true;
        }

        if ( !FileSystem::ensurePath( $_path = dirname( $file ) ) )
        {
            throw new \RuntimeException( 'Unable to create file "' . $file . '"' );
        }

        return file_put_contents( str_replace( ['{file}.{date}'], [basename( $file ), date( 'YmdHiS' )], $_template ), file_get_contents( $file ) );
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $_data = [];

        foreach ( array_keys( $this->_populatePropertyMap() ) as $_jsonKey => $_property )
        {
            $_data[$_jsonKey] = $this->{$_property};
        }

        return $_data;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     *
     * @return string
     */
    public function toJson( $options = self::DEFAULT_JSON_ENCODE_OPTIONS )
    {
        return $this->encode( $this->toArray(), $options );
    }

    /**
     * Populates the property map used for toArray/toJson
     *
     * @param bool $force If true, cache will be busted
     *
     * @return array
     */
    protected function _populatePropertyMap( $force = false )
    {
        if ( false === $force && !empty( $this->_propertyMap ) )
        {
            return $this->_propertyMap;
        }

        $_me = new \ReflectionClass( $this );
        $_map = [];

        foreach ( $_me->getProperties() as $_key => $_value )
        {
            $_map[$_key] = str_replace( '_', '-', trim( camel_case( $_key ), '_' ) );
        }

        return $this->_propertyMap = $_map;
    }

    /**
     * @param array|\stdClass $values
     *
     * @return $this
     */
    protected function _populateProperties( $values = null )
    {
        if ( !empty( $values ) )
        {
            $_map = array_flip( $this->getPropertyMap( true ) );

            foreach ( $values as $_key => $_value )
            {
                isset( $_map[$_key] ) && $this->{'set' . $_map[$_key]}( $_value );
            }
        }

        return $this->toArray();
    }

    /**
     * @param bool $force If true, cache will be busted
     *
     * @return array
     */
    public function getPropertyMap( $force = false )
    {
        return $this->_propertyMap ?: $this->_populatePropertyMap( $force );
    }

    /**
     * @return boolean
     */
    public static function isMakeBackups()
    {
        return static::$_makeBackups;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->_filePath;
    }

}
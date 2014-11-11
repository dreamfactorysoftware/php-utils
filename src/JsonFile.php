<?php
namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Utility\Exception\FileException;
use DreamFactory\Library\Utility\Exception\FileSystemException;
use Kisma\Core\Utility\Log;

/**
 * Reads/writes a json file
 * Can be used statically as well for various functionality
 */
class JsonFile
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
    /**
     * @type string The file naming template for backups
     */
    const BACKUP_FORMAT = '{file}.{date}.save';

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The absolute path of our JSON file.
     */
    protected $_filePath;
    /**
     * @type bool If true, a copy of files to be overwritten will be made
     */
    protected static $_makeBackups = true;

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
        $this->_filePath = static::ensureFileExists( dirname( $filePath ), basename( $filePath ), $defaultContents );
        static::$_makeBackups = $makeBackups;
    }

    /**
     * Reads the file
     *
     * @param bool $decoded If true (the default), the read data is decoded
     *
     * @throws FileSystemException
     * @return array|object
     */
    public function read( $decoded = true )
    {
        if ( !static::ensureFileExists( $this->_filePath ) )
        {
            throw new FileSystemException( 'The file "' . $this->_filePath . '" does not exist.' );
        }

        $_data = static::decodeFile( $this->_filePath );

        return $decoded ? $_data : static::encode( $_data );
    }

    /**
     * Writes the file
     *
     * @param array|object $data       The unencoded data to store
     * @param int          $options    Options for json_encode. Default is static::DEFAULT_JSON_ENCODE_OPTIONS
     * @param int          $retries    The number of times to retry the write.
     * @param float|int    $retryDelay The number of microseconds (100000 = 1s) to wait between retries
     *
     *
     * @throws FileSystemException
     * @throws \Exception
     */
    public function write( $data = array(), $options = self::DEFAULT_JSON_ENCODE_OPTIONS, $retries = self::STORAGE_OPERATION_RETRY_COUNT, $retryDelay = self::STORAGE_OPERATION_RETRY_DELAY )
    {
        static::encodeFile( $this->_filePath, $data, true, $options );
    }

    /**
     * Makes sure the file passed it exists. Create default config and saves otherwise.
     *
     * @param string       $filePath        The absolute path of the file, including the name.
     * @param array|object $defaultContents The contents to write to the file if being created
     *
     * @throws FileSystemException
     * @return string The absolute path to the file
     */
    public function ensureFileExists( $filePath, $defaultContents = null )
    {
        $_path = dirname( $filePath );

        if ( !is_dir( $_path ) || false === @mkdir( $_path, 0777, true ) )
        {
            if ( file_exists( $filePath ) )
            {
                throw new FileSystemException( $_path . ' exists but it is not a directory.' );
            }

            throw new FileSystemException( 'Unable to create directory: ' . $_path );
        }

        if ( !file_exists( $filePath ) )
        {
            static::encodeFile( $filePath, empty( $defaultContents ) ? new \stdClass : $defaultContents );
        }

        //  Exists
        return is_file( $filePath );
    }

    /**
     * JSON encodes data
     *
     * @param  mixed $data    Data to encode
     * @param  int   $options Options for json_encode. Default is static::DEFAULT_JSON_ENCODE_OPTIONS
     *
     * @return string Encoded json
     */
    public static function encode( $data, $options = self::DEFAULT_JSON_ENCODE_OPTIONS )
    {
        if ( false === ( $_json = json_encode( $data, $options ) ) || JSON_ERROR_NONE != json_last_error() )
        {
            throw new \InvalidArgumentException( 'The data could not be encoded: ' . json_last_error_msg() );
        }

        return $_json . ( $options & static::JSON_PRETTY_PRINT ? PHP_EOL : null );
    }

    /**
     * Encodes and writes contents to file
     *
     * @param string $file The absolute path to the file to encode
     * @param mixed  $data
     * @param int    $options
     *
     * @throws \Exception
     */
    public static function encodeFile( $file, $data, $options = self::JSON_UNESCAPED_SLASHES )
    {
        $data = static::encode( $data, $options );
        $_retries = static::STORAGE_OPERATION_RETRY_COUNT;

        $_fileName = basename( $file );
        $_fileCopy = dirname( $file ) . DIRECTORY_SEPARATOR . $_fileName . '.' . date( 'YmdHis' );

        if ( static::$_makeBackups && file_exists( $file ) )
        {
            if ( false === @copy( $file, $_fileCopy ) )
            {
                Log::notice( 'Could not make copy of existing file to "' . $_fileCopy . '"' );
            }
        }

        while ( $_retries-- )
        {
            try
            {
                if ( false === file_put_contents( $file, $data ) )
                {
                    throw new FileException( 'Unable to write data to file "' . $file . '".' );
                }

                break;
            }
            catch ( \Exception $_ex )
            {
                if ( $_retries )
                {
                    usleep( static::STORAGE_OPERATION_RETRY_DELAY );
                    continue;
                }

                throw $_ex;
            }
        }
    }

    /**
     * Decodes a JSON string
     *
     * @param string $json The data to decode
     * @param bool   $asArray
     * @param int    $depth
     * @param int    $options
     *
     * @return mixed
     */
    public static function decode( $json, $asArray = true, $depth = 512, $options = 0 )
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
    public static function decodeFile( $file, $asArray = true, $depth = 512, $options = 0 )
    {
        if ( !file_exists( $file ) || !is_readable( $file ) || false === ( $_json = file_get_contents( $file ) ) )
        {
            throw new \InvalidArgumentException( 'The file "' . $file . '" does not exist or cannot be read.' );
        }

        return static::decode( $_json, $asArray, $depth, $options );
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->_filePath;
    }

}
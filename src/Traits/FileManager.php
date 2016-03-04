<?php namespace DreamFactory\Library\Utility\Traits;

use DreamFactory\Library\Utility\Exceptions\FileException;
use DreamFactory\Library\Utility\Hasher;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * A trait that can manages files
 */
trait FileManager
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type array Our file descriptors
     */
    protected $fmResources = [];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Closes any open files and clears out descriptor cache
     */
    public function resetFileManager()
    {
        //  Close any open files
        foreach ($this->fmResources as $_key => $_map) {
            $this->closeFile($_map['resource']);
        }

        $this->fmResources = [];
    }

    /**
     * @param string      $key    The managed file key to which to write
     * @param string|null $data   The data to write
     * @param int|null    $length If specified, only write out this many bytes, or to end of string, whichever comes first
     *
     * @return int The number of bytes written
     */
    protected function writeFile($key, $data = null, $length = null)
    {
        if (false !== ($_file = $this->resolveKey($key))) {
            return fwrite($_file['resource'], $data, $length);
        }

        throw new InvalidArgumentException('The $key "' . $key . '" is invalid.');
    }

    /**
     * @param string $key    The managed file key to which to write
     * @param int    $length The number of bytes read
     *
     * @return int The number of bytes read
     */
    protected function readFile($key, $length)
    {
        if (false !== ($_file = $this->resolveKey($key))) {
            return fread($_file['resource'], $length);
        }

        throw new InvalidArgumentException('The $key "' . $key . '" is invalid.');
    }

    /**
     * Opens a file, returns an ID to use for future reference
     *
     * @param string     $filename
     * @param string     $mode
     * @param bool|null  $use_include_path
     * @param mixed|null $context
     *
     * @return string|bool A hash key representing this file in the cache, false on error.
     */
    protected function openFile($filename, $mode, $use_include_path = null, $context = null)
    {
        $this->closeFile($filename);

        try {
            if (false === ($_fd = @fopen($filename, $mode, $use_include_path, $context)) || !is_resource($_fd)) {
                throw new InvalidArgumentException('The file "' . $filename . '" could not be opened.');
            }

            //  Store for posterity
            $_file = array_merge($this->resolveKey($filename, true), ['resource' => $_fd, 'name' => $filename]);

            return $_file['hash'];
        } catch (Exception $_ex) {
            \Log::error('[php-utils.traits.file-manager] error opening file "' . $filename . '"');

            return false;
        }
    }

    /**
     * @param string $key The file's key
     *
     * @return bool
     */
    protected function closeFile($key)
    {
        if (false === ($_file = $this->resolveKey($key)) || empty(array_get($_file, 'resource'))) {
            return false;
        }

        try {
            if (false !== @fclose($_file['resource'])) {
                array_forget($this->fmResources, $_file['hash']);

                return true;
            }
        } catch (Exception $_ex) {
            /** @noinspection PhpUndefinedMethodInspection */
            Log::error('[php-utils.traits.file-manager] error closing supposedly open file "' . $_file['name'] . '"');
        }

        return false;
    }

    /**
     * Returns the entire file as a string
     *
     * @param string        $key     The managed file key to which to read
     * @param string|null   $flags   Any flags to pass to file_get_contents()
     * @param resource|null $context An optional valid context resource created with stream_context_create
     * @param int|null      $offset  Where to start reading. Defaults to the beginning
     * @param int|null      $maxlen  The max number of bytes to read. Defaults to all
     *
     * @return int The number of bytes read
     */
    protected function getFile($key, $flags = null, $context = null, $offset, $maxlen = null)
    {
        if (false === ($_file = $this->resolveKey($key))) {
            throw new InvalidArgumentException('The $key "' . $key . '" is invalid.');
        }

        return file_get_contents($_file['name'], $flags, $context, $offset, $maxlen);
    }

    /**
     * Returns the entire file as a string
     *
     * @param string        $key      The managed file key to which to read
     * @param string|null   $contents The data to write
     * @param string|null   $flags    Any flags to pass to file_get_contents()
     * @param resource|null $context  An optional valid context resource created with stream_context_create
     *
     * @return int The number of bytes written
     */
    protected function putFile($key, $contents, $flags, $context = null)
    {
        if (false === ($_file = $this->resolveKey($key))) {
            throw new InvalidArgumentException('The $key "' . $key . '" is invalid.');
        }

        return file_put_contents($_file['name'], $contents, $flags, $context);
    }

    /**
     * @param string        $key The key of the file to delete
     * @param resource|null $context
     *
     * @return bool
     */
    protected function unlinkFile($key, $context = null)
    {
        try {
            $_file = $this->resolveKey($key);

            if (false === @unlink($_file['name'], $context)) {
                throw new FileException('Unable to unlink file "' . $_file['name'] . '"');
            }

            return true;
        } catch (Exception $_ex) {
            \Log::error('[php-utils.traits.file-manager] error unlinking $key "' . $key . '"');

            return false;
        }
    }

    /**
     * Makes a hashed file key
     *
     * @param string $filename
     *
     * @return string
     */
    protected function makeFileKey($filename)
    {
        return Hasher::make(config('app.key') . $filename);
    }

    /**
     * Returns an array of information about the file, if known.
     *
     * @param string $key
     * @param bool   $create If the key was not found, one will be generated
     *
     * @return array|bool
     */
    protected function resolveKey($key, $create = false)
    {
        if (false === ($_file = array_get($this->fmResources, $key, false))) {
            if (false === ($_file = $this->resolveFile($key))) {
                return $create ? ['name' => null, 'resource' => null, 'hash' => $this->makeFileKey($key)] : false;
            }
        }

        return $_file;
    }

    /**
     * If the file is under management, it's resource array is returned. False otherwise
     *
     * @param string $filename
     *
     * @return array|bool
     */
    protected function resolveFile($filename)
    {
        foreach ($this->fmResources as $_file) {
            if ($_file['name'] == $filename) {
                return $_file;
            }
        }

        return false;
    }
}

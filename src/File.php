<?php namespace DreamFactory\Library\Utility;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Simple file wrapper
 */
class File
{
    //********************************************************************************
    //* Members
    //********************************************************************************

    /**
     * @var string The name of the current file
     */
    protected $_name = false;
    /**
     * @var \resource The handle of the current file
     */
    protected $_resource = false;

    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * @param string $fileName
     */
    public function __construct($fileName)
    {
        $this->_name = $fileName;
    }

    /**
     * @return bool
     */
    public function validHandle()
    {
        return (false !== $this->_resource);
    }

    /**
     * @return bool
     */
    public function open()
    {
        $this->close();

        if (!file_exists($this->_name)) {
            throw new FileNotFoundException($this->_name);
        }

        $this->_resource = @fopen($this->_name, 'r');

        return $this->validHandle();
    }

    /**
     * Close the file
     */
    public function close()
    {
        if ($this->_resource) {
            @fclose($this->_resource);
        }

        $this->_resource = false;
    }

    /**
     * @return int|bool
     */
    public function filesize()
    {
        return $this->validHandle() ? filesize($this->_name) : false;
    }

    /**
     * @return int|bool
     */
    public function atime()
    {
        return $this->validHandle() ? fileatime($this->_name) : false;
    }

    /**
     * @return int|bool
     */
    public function fileowner()
    {
        return $this->validHandle() ? fileowner($this->_name) : false;
    }

    /**
     * @return int|bool
     */
    public function filegroup()
    {
        return $this->validHandle() ? filegroup($this->_name) : false;
    }

    /**
     * @param int $offset
     * @param int $whence
     *
     * @return int|bool
     */
    public function fseek($offset = 0, $whence = SEEK_SET)
    {
        return $this->validHandle() ? fseek($this->_resource, $offset, $whence) : false;
    }

    /**
     * @return int|bool
     */
    public function ftell()
    {
        return $this->validHandle() ? ftell($this->_resource) : false;
    }

    /**
     * Retrieves a string from the current file
     *
     * @param int $length
     *
     * @return string|bool
     */
    public function fgets($length = null)
    {
        return $this->validHandle() ? fgets($this->_resource, $length) : false;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return int
     */
    public function getResource()
    {
        return $this->_resource;
    }
}

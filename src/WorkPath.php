<?php namespace DreamFactory\Library\Utility;

use League\Flysystem\Filesystem;

/**
 * A utility that manages working directories/space
 */
class WorkPath
{
    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string The path of the space
     */
    protected $path;
    /**
     * @type Filesystem The filesystem to use
     */
    protected $filesystem;

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param \League\Flysystem\Filesystem $filesystem
     * @param string|null                  $prefix An optional prefix for the created space
     *
     * @return static
     */
    public static function make(Filesystem $filesystem, $prefix = null)
    {
        return new static($filesystem, $prefix);
    }

    /**
     * @param \League\Flysystem\Filesystem $filesystem
     * @param string|null                  $prefix An optional prefix for the created space
     */
    public function __construct(Filesystem $filesystem, $prefix = null)
    {
        $this->filesystem = $filesystem;
        $this->path = $prefix . sha1(microtime(true) . microtime(true));

        $this->clear();
    }

    /**
     * Removes and recreates the working path
     */
    public function clear()
    {
        if ($this->filesystem->has($this->path)) {
            $this->filesystem->deleteDir($this->path);
        }

        $this->filesystem->createDir($this->path);
    }

    /**
     * Deletes the working space when destructed
     */
    public function __destruct()
    {
        if ($this->filesystem->has($this->path)) {
            $this->filesystem->deleteDir($this->path);
        }
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

}
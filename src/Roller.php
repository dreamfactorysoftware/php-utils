<?php namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Utility\Exceptions\FileException;

/**
 * Simple file roller
 */
class Roller
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int The default number of files to keep
     */
    const DEFAULT_KEEP_COUNT = 7;

    //********************************************************************************
    //* Members
    //********************************************************************************

    /**
     * @var string The number of copies to keep
     */
    protected $_keepCount = self::DEFAULT_KEEP_COUNT;
    /**
     * @var string The name of the file to roll
     */
    protected $_filename;
    /**
     * @type string The path to the file
     */
    protected $_path;

    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * @param string $fileName
     * @param int    $keepCount
     */
    public function __construct($fileName, $keepCount = self::DEFAULT_KEEP_COUNT)
    {
        $this->_filename = basename($fileName);
        $this->_path = dirname($fileName);
    }

    /**
     * The file roller
     */
    public function rollFiles()
    {
        $_baseFile = $this->_path . DIRECTORY_SEPARATOR . $this->_filename;

        //  Roll the files 1-n
        for ($_i = $this->_keepCount - 1; $_i > 0; $_i--) {
            $_oldName = $_baseFile . '.' . ($_i - 1);
            $_newName = $_baseFile . '.' . $_i;

            //  If this is the last one, remove it...
            if (file_exists($_oldName)) {
                if (false === @rename($_oldName, $_newName)) {
                    throw new FileException('Unable to rename file "' . $_oldName . '" to "' . $_newName . '"');
                }
            }
        }

        //  Roll the base file...
        if (false === @rename($_baseFile, $_baseFile . '.1')) {
            throw new FileException('Unable to rename file "' . $_baseFile . '" to "' . $_baseFile . '.1"');
        }
    }
}
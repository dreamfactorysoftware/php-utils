<?php
namespace DreamFactory\Library\Utility;

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
    public function __construct( $fileName, $keepCount = self::DEFAULT_KEEP_COUNT )
    {
        $this->_filename = basename( $fileName );
        $this->_path = dirname( $fileName );
    }

    public function rollFiles()
    {
        for ( $_i = $this->_keepCount; $_i >= 0; $_i-- )
        {
            )
        }
    }
}

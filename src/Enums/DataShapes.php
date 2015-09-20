<?php namespace DreamFactory\Library\Utility\Enums;

/**
 * Various formats in which data may be presented
 */
class DataShapes extends FactoryEnum
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @type int Raw/Verbatim
     */
    const RAW = 0;
    /**
     * @type int MediaWiki table format
     */
    const MEDIAWIKI_TABLE = 1;
    /**
     * @type int JSON
     */
    const JSON = 2;
    /**
     * @type int XML
     */
    const XML = 3;
}

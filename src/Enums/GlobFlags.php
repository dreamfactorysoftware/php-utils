<?php namespace DreamFactory\Library\Utility\Enums;

/**
 * GlobFlags
 * Ya know, for globbing...
 */
class GlobFlags extends FactoryEnum
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @type int
     */
    const GLOB_NODIR = 0x0100;
    /**
     * @type int
     */
    const GLOB_PATH = 0x0200;
    /**
     * @type int
     */
    const GLOB_NODOTS = 0x0400;
    /**
     * @type int
     */
    const GLOB_RECURSE = 0x0800;
    /**
     * @type int
     */
    const NoDir = 0x0100;
    /**
     * @type int
     */
    const Path = 0x0200;
    /**
     * @type int
     */
    const NoDots = 0x0400;
    /**
     * @type int
     */
    const Recurse = 0x0800;
}
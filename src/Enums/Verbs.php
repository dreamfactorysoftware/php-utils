<?php
namespace DreamFactory\Library\Utility\Enums;

use Kisma\Core\Enums\SeedEnum;

/**
 * All the HTTP verbs in a single place!
 */
class Verbs extends SeedEnum
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string
     */
    const GET = 'GET';
    /**
     * @type string
     */
    const PUT = 'PUT';
    /**
     * @type string
     */
    const HEAD = 'HEAD';
    /**
     * @type string
     */
    const POST = 'POST';
    /**
     * @type string
     */
    const DELETE = 'DELETE';
    /**
     * @type string
     */
    const OPTIONS = 'OPTIONS';
    /**
     * @type string
     */
    const COPY = 'COPY';
    /**
     * @type string
     */
    const PATCH = 'PATCH';
    /**
     * @type string
     */
    const MERGE = 'MERGE';
    /**
     * @type string
     */
    const TRACE = 'TRACE';
    /**
     * @type string
     */
    const CONNECT = 'CONNECT';
}
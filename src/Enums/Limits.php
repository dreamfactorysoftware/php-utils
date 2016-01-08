<?php namespace DreamFactory\Library\Utility\Enums;

/**
 * Various constants for limits
 */
class Limits extends FactoryEnum
{
    /** @type int not used */
    const NOTUSED = 0;

    /** @type int limit type cluster */
    const CLUSTER = 1;

    /** @type int limit type instance */
    const INSTANCE = 2;

    /** @type int limit type user */
    const USER = 3;
}
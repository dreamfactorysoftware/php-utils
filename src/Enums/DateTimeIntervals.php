<?php
namespace DreamFactory\Library\Utility\Enums;

/**
 * Various date and time constants
 */
class DateTimeIntervals extends FactoryEnum
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @var int
     */
    const __default = self::SECONDS_PER_MINUTE;

    /**
     * @var int Microseconds per hour
     */
    const US_PER_HOUR = 3600000;
    /**
     * @var int Microseconds per minute
     */
    const US_PER_MINUTE = 60000;
    /**
     * @var int Microseconds per second
     */
    const US_PER_SECOND = 1000000;
    /**
     * @var int Milliseconds per second
     */
    const MS_PER_SECOND = 1000;
    /**
     * @var int
     */
    const SECONDS_PER_MINUTE = 60;
    /**
     * @var int
     */
    const SECONDS_PER_HOUR = 3600;
    /**
     * @var int
     */
    const SECONDS_PER_DAY = 86400;
    /**
     * @var int circa 01/01/1980 (Ahh... my TRS-80... good times)
     */
    const EPOCH_START = 315550800;
    /**
     * @var int circa 01/01/2038 (despite the Mayan calendar or John Titor...)
     */
    const EPOCH_END = 2145934800;
}

<?php namespace DreamFactory\Library\Utility\Tests\Enums;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * An enum class for use with testing FactoryEnum
 * Contains six constants that define scalar types.
 */
class TestEnum extends FactoryEnum
{
    const DOUBLE        = 1.2345;
    const INTEGER       = 12345;
    const STRING        = '12345';
    const BOOLEAN_TRUE  = true;
    const BOOLEAN_FALSE = false;
    const NULL          = null;
}

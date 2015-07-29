<?php namespace DreamFactory\Library\Utility\Enums;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * DFE constants
 */
class EnterpriseDefaults extends FactoryEnum
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /** @type string The name of the cluster manifest file */
    const CLUSTER_MANIFEST_FILE = '.dfe.cluster.json';
    /**
     * @var string
     */
    const MAINTENANCE_MARKER = '/var/www/.maintenance';
    /**
     * @var string
     */
    const DFE_MARKER = '/var/www/.dfe-managed';

    const CONSOLE_X_HEADER = 'X-DreamFactory-Console-Key';
}
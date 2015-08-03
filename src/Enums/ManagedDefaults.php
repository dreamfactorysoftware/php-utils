<?php namespace DreamFactory\Library\Utility\Enums;

/**
 * Constants for managed instances
 */
class ManagedDefaults extends FactoryEnum
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
    const MANAGED_INSTANCE_MARKER = '/var/www/.dfe-managed';
    /**
     * @var string
     */
    const DFE_MARKER = '/var/www/.dfe-managed';
    /**
     * @string
     */
    const CONSOLE_X_HEADER = 'X-DreamFactory-Console-Key';
}
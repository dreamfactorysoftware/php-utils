<?php namespace DreamFactory\Library\Utility\Enums;

/**
 * Constants shared between DreamFactory v2.x and DreamFactory Enterprise v1.x
 */
class EnterpriseDefaults extends FactoryEnum
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string HTTP header used to speak with the creator
     */
    const CONSOLE_X_HEADER = 'X-DreamFactory-Console-Key';
    /**
     * @type string The managed instance provisioner manifest file name
     */
    const CLUSTER_MANIFEST_FILE = '.dfe.cluster.json';
    /**
     * @type string An absolute path to a file whose very existence implies that the instance is under management
     */
    const DFE_MARKER = '/var/www/.dfe-managed';
    /**
     * @type string An absolute path to a file whose very existence implies that the instance is under management
     */
    const MAINTENANCE_MARKER = '/var/www/.maintenance';
    /**
     * @type string
     */
    const MANAGED_INSTANCE_MARKER = '/var/www/.managed';
}
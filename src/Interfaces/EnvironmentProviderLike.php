<?php namespace DreamFactory\Library\Utility\Interfaces;

/**
 * An object that can provide certain runtime environment details
 *
 * These details should be kept to a minimum, and be considered as
 * bootstrap/start-up/pre-flight application globals
 */
interface EnvironmentProviderLike
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /** @type string */
    const KEY_USER_NAME = 'environment.user_name';
    /** @type string */
    const KEY_HOSTNAME = 'environment.hostname';
    /** @type string */
    const KEY_MOUNT_POINT = 'environment.mount_point';
    /** @type string */
    const KEY_ZONE = 'environment.zone';
    /** @type string */
    const KEY_PARTITION = 'environment.partition';
    /** @type string */
    const KEY_TEMP_PATH = 'environment.temp_path';
    /** @type string */
    const KEY_INSTALL_PATH = 'environment.install_path';
    /** @type string */
    const KEY_REQUEST = 'environment.request';
    /** @type string */
    const KEY_RESPONSE = 'environment.response';
    /** @type string */
    const KEY_STORAGE_ID = 'environment.storage_id';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Returns, by default, a SHA256 hash of a string that can be used as a key for caching
     *
     * @param string|int $entropy   Any additional entropy to add to the concatenation before hashing
     * @param string     $algorithm The algorithm to use when hashing. Defaults to SHA256
     *
     * @return string
     */
    public function getRequestId($algorithm = 'sha256', $entropy = null);

    /**
     * Determine the host name of this machine. First HTTP_HOST is used from PHP $_SERVER if available. Otherwise the
     * PHP gethostname() call is used.
     *
     * @param bool $fqdn If true, the fully qualified domain name is returned. Otherwise just the first portion.
     *
     * @return string
     */
    public function getHostname($fqdn = true);

    /**
     * Try a variety of cross platform methods to determine the current user
     *
     * @return bool|string
     */
    public function getUserName();

    /**
     * Gets a temporary path suitable for writing by the current user...
     *
     * @param string $subPath The sub-directory of the temporary path created that is required
     *
     * @return bool|string
     */
    public function getTempPath($subPath = null);

    /**
     * Locates the installed platform's base directory
     *
     * @return string|bool The absolute path to the platform installation. False if not found
     */
    public function getInstallPath();

}
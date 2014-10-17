<?php
namespace DreamFactory\Library\Utility;

use Kisma\Core\Components\Flexistore;

/**
 * An application level cache. Wrapper around Flexistore that is smarter about where file caches are stored.
 */
class AppCache
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @type string
     */
    const DEFAULT_NAMESPACE = 'app';
    /**
     * @type int Only cache for 5 minutes max
     */
    const DEFAULT_CACHE_TTL = 300;
    /**
     * @type string
     */
    const DEFAULT_SUB_PATH = '/.app_cache';

    //********************************************************************************
    //* Members
    //********************************************************************************

    /**
     * @type string The storage namespace
     */
    protected $_namespace = self::DEFAULT_NAMESPACE;
    /**
     * @type Flexistore Our cache object
     */
    protected $_cache;
    /**
     * @type string The name/id of the cache
     */
    protected $_cacheId = null;
    /**
     * @type string The path to the cache file
     */
    protected $_cachePath = null;
    /**
     * @type array The cache configuration
     */
    protected $_cacheConfig = array();

    //********************************************************************************
    //* Public Methods
    //********************************************************************************

    /**
     * @param string $cacheId   The name/id of the cache.
     * @param string $namespace The namespace of this cache
     * @param array  $config    Any additional configuration parameters
     */
    public function __construct( $cacheId, $namespace = self::DEFAULT_NAMESPACE, array $config = array() )
    {
        $this->_namespace = $namespace;
        $this->_cacheId = $cacheId;
        $this->_cacheConfig = $config;

        $this->_cache = $this->_initialize();
    }

    /**
     * Initialize the cache
     */
    protected function _initialize()
    {
        $_memcache =
            isset( $this->_cacheConfig, $this->_cacheConfig['memcached'] ) &&
            is_array( $this->_cacheConfig['memcached'] )
                ? $this->_cacheConfig['memcached']
                : false;

        //  Use memcached if it's available and we can...
        if ( !empty( $_memcached ) && extension_loaded( 'memcached' ) && class_exists( '\\Memcached', false ) )
        {
            try
            {
                return Flexistore::createMemcachedStore( $_memcache );
            }
            catch ( \RuntimeException $_ex )
            {
                //  No memcache :(
            }
        }

        $_redis =
            isset( $this->_cacheConfig, $this->_cacheConfig['redis'] ) &&
            is_array( $this->_cacheConfig['redis'] )
                ? $this->_cacheConfig['redis']
                : false;

        //  Use redis if it's available and we can...
        if ( !empty( $_redis ) && extension_loaded( 'redis' ) && class_exists( '\\Redis', false ) )
        {
            try
            {
                return Flexistore::createRedisStore( $_redis );
            }
            catch ( \RuntimeException $_ex )
            {
                //  No memcache :(
            }
        }

        //  Get private directory
        if ( false === ( $this->_cachePath = Environment::getTempPath( static::DEFAULT_SUB_PATH ) ) )
        {
            throw new \RuntimeException( 'Cannot find a suitable temporary directory for the cache.' );
        }

        //  Create a file store
        return Flexistore::createFileStore( $this->_cachePath, '.cache', static::DEFAULT_NAMESPACE );
    }

    /**
     * Flushes the config from the cache
     *
     * @param string $id The id to remove from the cache, or null for all items
     *
     * @return bool
     */
    public function flush( $id = null )
    {
        return
            null !== $id
                ? $this->delete( $id )
                : $this->deleteAll();
    }

    /** @inheritdoc */
    public function set( $id, $value, $ttl = self::DEFAULT_CACHE_TTL )
    {
        $this->_cache->set( $id, $value, $ttl );
    }

    /** @inheritdoc */
    public function get( $id, $defaultValue = null, $remove = false )
    {
        return $this->_cache->get( $id, $defaultValue, $remove );
    }

    /** @inheritdoc */
    public function fetch( $id, $defaultValue = null, $remove = false )
    {
        return $this->_cache->fetch( $id, $defaultValue, $remove );
    }

    /** @inheritdoc */
    public function contains( $id )
    {
        return
            false !== $this->_cache->fetch( $id );
    }

    /** @inheritdoc */
    public function delete( $id )
    {
        return $this->_cache->delete( $id );
    }

    /** @inheritdoc */
    public function deleteAll()
    {
        return $this->_cache->deleteAll();
    }

    /** @inheritdoc */
    public function getStats()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->_cache->getStats();
    }
}

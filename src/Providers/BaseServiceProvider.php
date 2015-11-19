<?php namespace DreamFactory\Library\Utility\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * A base class for laravel 5.1+ service providers
 */
abstract class BaseServiceProvider extends ServiceProvider
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string The name of the service in the IoC
     */
    const IOC_NAME = false;

    //********************************************************************************
    //* Public Methods
    //********************************************************************************

    /**
     * Called after construction
     */
    public function boot()
    {
        //  Does nothing but encourages calling the parent method
    }

    /**
     * Register a shared binding in the container.
     *
     * @param  string               $abstract
     * @param  \Closure|string|null $concrete
     *
     * @return void
     */
    public function singleton($abstract, $concrete)
    {
        //  Register object into instance container
        $this->app->singleton($abstract ?: static::IOC_NAME, $concrete);
    }

    /**
     * Register a binding with the container.
     *
     * @param  string|array         $abstract
     * @param  \Closure|string|null $concrete
     * @param  bool                 $shared
     *
     * @return void
     */
    public function bind($abstract, $concrete, $shared = false)
    {
        //  Register object into instance container
        $this->app->bind($abstract ?: static::IOC_NAME, $concrete, $shared);
    }

    /**
     * @return array
     */
    public function provides()
    {
        return
            static::IOC_NAME
                ? array_merge(parent::provides(), [static::IOC_NAME,])
                : parent::provides();
    }

    /**
     * Returns the service configuration either based on class name or argument name. Override method to provide custom configurations
     *
     * @param string|null $name
     * @param array       $default
     *
     * @return array
     */
    public static function getServiceConfig($name = null, $default = [])
    {
        if (empty($_key = $name)) {
            $_mirror = new \ReflectionClass(get_called_class());
            $_key = snake_case(str_ireplace(['ServiceProvider', 'Provider'], null, $_mirror->getShortName()));
            unset($_mirror);
        }

        return config($_key, $default);
    }

    /**
     * @return string Returns this provider's IoC name
     */
    public function __invoke()
    {
        return static::IOC_NAME ?: null;
    }

    /**
     * @param Application|null $app
     *
     * @return mixed
     */
    public static function service(Application $app = null)
    {
        $app = $app ?: app();

        return $app->make(static::IOC_NAME);
    }
}

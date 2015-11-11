<?php namespace DreamFactory\Library\Utility\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * A base class for service providers
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
        return [static::IOC_NAME];
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
        if (null === ($_name = $name)) {
            $_mirror = new \ReflectionClass(get_called_class());
            $_name = snake_case(str_ireplace('ServiceProvider', null, $_mirror->getShortName()));
            unset($_mirror);
        }

        return config($_name, $default);
    }

    /**
     * @return string Returns this provider's IoC name
     */
    public function __invoke()
    {
        return static::IOC_NAME ?: null;
    }

    /**
     * Makes and returns an instance of this provider's service
     *
     * @return mixed
     */
    public static function service()
    {
        return app(static::IOC_NAME);
    }
}

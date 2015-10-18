<?php namespace DreamFactory\Library\Utility\Providers;

use DreamFactory\Enterprise\Common\Services\InspectionService;
use Illuminate\Support\ServiceProvider;

class InspectionServiceProvider extends ServiceProvider
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /** @inheritdoc */
    const IOC_NAME = 'dfe-installer.inspection';

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /** @inheritdoc */
    public function register()
    {
        //  Register the manager
        $this->app->singleton(static::IOC_NAME,
            function ($app) {
                return new InspectionService($app);
            });
    }
}

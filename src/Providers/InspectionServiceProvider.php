<?php namespace DreamFactory\Library\Utility\Providers;

use DreamFactory\Library\Utility\Services\InspectionService;

class InspectionServiceProvider extends BaseServiceProvider
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
        //  Register the service
        $this->app->singleton(
            static::IOC_NAME,
            function ($app){
                return new InspectionService($app);
            }
        );
    }
}

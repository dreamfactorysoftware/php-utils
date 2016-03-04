<?php namespace DreamFactory\Library\Utility;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Generic laravel utilities
 */
class Laravel
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Calls the Artisan command "key:generate" creating and app key
     *
     * @return string|null The generated app key
     */
    public static function makeAppKey()
    {
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            Artisan::call('key:generate', ['--show' => true, '--no-ansi' => true,]);

            return trim(\Artisan::output(), ' ' . PHP_EOL);
        } catch (Exception $_ex) {
            /** @noinspection PhpUndefinedMethodInspection */
            Log::error('[php-utils.laravel.make-app-key] ' . $_ex->getMessage());
        }

        return null;
    }
}

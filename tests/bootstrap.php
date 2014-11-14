<?php
/**
 * @type bool
 */
const TEST_BOOTSTRAP = true;

/**
 * bootstrap.php
 * Bootstrap script for PHPUnit tests
 */
$_basePath = dirname( __DIR__ );
$_vendorPath = $_basePath . '/vendor';

if ( !is_dir( $_vendorPath ) )
{
    echo 'Please run composer install/update before running tests.';
    exit( 1 );
}

//	Composer
$_autoloader = require( $_vendorPath . '/autoload.php' );

//  Test config
file_exists( __DIR__ . '/config/test.config.php' ) && require( __DIR__ . '/config/test.config.php' );

//	Testing keys
file_exists( __DIR__ . '/config/keys.php' ) && require_once( __DIR__ . '/config/keys.php' );

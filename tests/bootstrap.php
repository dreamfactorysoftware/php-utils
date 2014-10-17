<?php
/**
 * This file is part of the DreamFactory Console Tools Library
 *
 * Copyright 2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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

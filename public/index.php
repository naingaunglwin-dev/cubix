<?php

/*
 |-----------------------------------------------------------------------------------------
 | Version Check
 |-----------------------------------------------------------------------------------------
 |
 | This step verifies whether the PHP version meets the requirements of the framework.
 | If the PHP version does not meet the required criteria, an error will be displayed,
 | and the application will terminate to prevent potential compatibility issues and
 | optimal performance.
 |
 */
if (version_compare(phpversion(), '8.4', '<')) {
    exit(
    sprintf(
        "PHP version %s or newer is required. Current version: %s",
        '8.4',
        phpversion()
    )
    );
}

# define a short constant version for DIRECTORY_SEPARATOR
define('DS', DIRECTORY_SEPARATOR);

/*
 |---------------------------------------------------------------------
 | Root Directory Constant Definition
 |---------------------------------------------------------------------
 |
 | Sets DIR_ROOT as the parent directory of the current directory,
 | establishing the base path for the application structure.
 |
*/
define('DIR_ROOT', dirname(__DIR__));

/*
 |---------------------------------------------------------------------
 | Core Constants Inclusion
 |---------------------------------------------------------------------
 |
 | Loads the constants.php file from the Foundation library directory,
 | bringing in essential constant definitions for the framework.
 |
*/
require_once join(DS, [DIR_ROOT, 'lib', 'Foundation', 'constant.php']);

/*
 |---------------------------------------------------------------------
 | Composer Autoloader Initialization
 |---------------------------------------------------------------------
 |
 | Includes Composer's autoload file to enable PSR-4 autoloading,
 | automatically loading all vendor dependencies and classes.
 |
*/
require "../vendor/autoload.php";

/*
 |-------------------------------------------------------------------
 | Boot Application
 |-------------------------------------------------------------------
 |
 | Loads the `app.php` bootstrap file and calls the
 | `boot()` method of the `Application` class to finalize
 | the bootstrapping process.
 |
 | @see DIR_BOOTSTRAP . 'app.php'  # Bootstrap config file
 | @see \Cubix\Foundation\Application::boot()  # Bootstraps the app
 |
 */
(require DIR_BOOTSTRAP . 'app.php')
    ->boot();

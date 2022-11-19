<?php
/*
 * Startup library
 *
 * This is a library with some executable code, it will just start up the
 * system. Include this library in any and every script or web page where you
 * want to use BASE.
 *
 * The startup library is different from all other libraries in that it is the
 * only library that actually executes code upon being included. It boots the
 * BASE system, configures PHP, loads requires libraries and then returns
 * control back to the script that called it.
 *
 * This library contains some directly executed code, some basic system
 * functions, and a number of functions that were stripped from other libraries
 * (notably a number of str_*() and array_*()functions) because they are
 * required by the startup library before these other libraries can be loaded.
 *
 * Upon startup, it sets a number of basic defines (framework version, php
 * minimum version, startup time, unique request id, a number of basic paths),
 * sets the PHP error handler and uncaught exception handlers (the former causes
 * every error in BASE to be an exception, the latter will handle every
 * exception that has not been caught by the scripts), and starts the core
 * object
 *
 * The core object, on startup, will determine what platform is being used
 * (Either command line -CLI- or a webserver -HTTP-) and for HTTP calls, it will
 * determine the "call type". The call type is basically the type of page being
 * requested (a normal HTTP page, an AJAX request, etc)
 *
 * List of available call types:
 * cli     A command line script
 * http    A normal HTTP page
 * admin   A normal HTTP page in the /admin/ section
 * api     An API request over HTTP coming from another computer / server
 * ajax    An AJAX request over HTTP coming from a browser, generated by another page
 * amp     A google AMP page
 * system  A system HTTP page which basically is any non HTTP 200 / 304 page, like 404, 500, etc
 *
 * The core object will then run the startup script for the determined call
 * type, called a "call type handlers". Each startup script will initialize BASE
 * in a different way, fulfilling the requirements for that specific call type.
 * A "cli" call type, for example, will require the CLI library, and will
 * process default command line parameters, whereas the "http" call type will
 * automatically load the http and html libraries and process cookies, and the
 * "api" library will automatically load the "json" library.
 *
 * All call type handlers are located in PATH_ROOT/libs/handlers/system-CALLTYPE.php
 *
 * This library also defines the CoreException class which is the default exception
 * thrown by BASE functions.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package startup
 */



/*
 * Load the system library only if the system library hasn't been loaded yet by
 * the route library
 *
 * Set LIBS here because the system library MAY be loaded  by the router
 * library, in which case they will both be loaded from /en/.
 */
if (!class_exists('core')) {
    require_once(__DIR__.'/system.php');
}



/*
 * Run the startup sequence
 */
$core->startup();
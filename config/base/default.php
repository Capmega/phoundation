<?php
/*
 * MAIN PHOUNDATION CONFIGURATION FILE
 *
 * DO NOT MODIFY THIS FILE!
 *
 * This file contains default valuesthat may be overwritten when you perform a
 * system update! Always update the following configuration files if you need to
 * make configuration changes
 *
 * production.php
 * ENVIRONMENT.php (Where ENVIRONMENT is the environment for which you wish to change the configuration)
 *
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY, ONLY "debug" IS ON TOP FOR CONVENIENCE
 *
 * @author Sven Oostenbrink <support@capmega.com>,
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Configuration
 * @package system
 */

// To debug or not to debug?
$_CONFIG['debug']              = array('enabled'                            => false,      // If set to true, the system will run in debug mode, the debug.php library will be loaded, and debug functions will be available.
                                       'bar'                                => 'limited'); // true|false|"limited" If set to true, base will display a debug bar that with CSS can be positioned @ the bottom of the screen. When set to limited, only authenticated users with the right "debug" will be able to see the debug bar

// AJAX configuration
$_CONFIG['ajax']               = array('domain'                             => '',
                                       'autosuggest'                        => array('min_characters'     => 2,
                                                                                     'default_results'    => 5,
                                                                                     'max_results'        => 15),
                                       'domain'                             => '',
                                       'prefix'                             => 'ajax/');

// Google AMP configuration
$_CONFIG['amp']                = array('enabled'                            => false);

// Avatar configuration, default avatar image, type will be added after this string, e.g.  _48x48.jpg
$_CONFIG['avatars']            = array('default'                            => '/pub/img/img_avatar',

                                       'types'                              => array('small'              => '100x100xthumb-circle',
                                                                                     'medium'             => '200x200xthumb-circle',
                                                                                     'large'              => '400x400xthumb'),

                                       'get_order'                          => array('facebook',
                                                                                     'google',
                                                                                     'microsoft'));

// Blog configuration
$_CONFIG['blogs']               = array('enabled'                           => false,
                                        'url'                               => '/%seocategory1%/%date%/%seoname%.html');

// Use bootstrap?
$_CONFIG['bootstrap']           = array('enabled'                           => true,
                                        'css'                               => 'bootstrap',
                                        'js'                                => 'bootstrap');

//
$_CONFIG['cache']              = array('method'                             => 'file',                                                  // "file", "memcached" or false.
                                       'max_age'                            => 86400,                                                   // Max local cache age is one day
                                       'key_hash'                           => 'sha256',
                                       'key_interlace'                      => 3,
                                       'http'                               => array('enabled'            => true,                      // Enable HTTP cache or not. If set to "auto", use PHP caching. Set to "auto" to have PHP manage the caching headers. See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
                                                                                     'cacheability'       => 'private',                 //
                                                                                     'expiration'         => 'max-age=604800',          //
                                                                                     'revalidation'       => 'must-revalidate',         //
                                                                                     'other'              => 'no-transform'));          //

// CDN configuration
$_CONFIG['cdn']                = array('min'                                => true,                                                    // If set to "true" all CSS and JS files loaded with html_load_js() and html_load_css() will be loaded as file.min.js instead of file.js. Use "true" in production environment, "false" in all other environments

                                       'cache_max_age'                      => 604800,                                                  // Max age of cached CDN files like bundle files, internal js files, etc. before they are deleted and regenerated. If cache age is lower than 60 it will be considered zero, and cache age will be ignored

                                       'enabled'                            => false,                                                   // If set to "true", base will try to use configured CDN servers for the content files. If set to false, files will be used from the local server

                                       'is_server'                          => false,                                                   // If set to "true", this server can function as a CDN server

                                       'copies'                             => 2,                                                       // Required amount of copies of each files. NOTE: This amount should be lower or equal to the amount of available CDN servers! (would not make sense otherwise)

                                       'domain'                             => '',                                                      //

                                       'bundler'                            => true,                                                    // If JS and CSS bundler should be enabled or not

                                       'css'                                => array('load_delayed'       => false,                     // If set to true, the CSS files will by default NOT be loaded in the <head> tag but at the end of the HTML <body> code so that the site will load faster. This may require some special site design to avoid problems though!
                                                                                     'post'               => false,                     // The default last CSS file to be loaded (after all others have been loaded, so that this one can override any CSS rule if needed)
                                                                                     'purge'              => false),                    // If specified true, the CSS files will be purged  before being sent to the client

                                       'img'                                => array('lazy_load'          => false,                     // If set to true, the image will use lazy loading automatically

                                                                                     'default'            => 'img/default.jpg',         // The default image to show if a requested image was not found on the CDN system

                                                                                     'auto_convert'       => array('jpg'  => false,     // If not false, automatically convert jpg images to the specified format. Supported types are: webp.
                                                                                                                   'gif'  => false,     // If not false, automatically convert gif images to the specified format. Supported types are: webp.
                                                                                                                   'png'  => false,     // If not false, automatically convert png images to the specified format. Supported types are: webp.
                                                                                                                   'webp' => false),    // If not false, automatically convert webp images to the specified format. Supported types are: webp.

                                                                                     'auto_resize'        => false),                    // If not false, automatically resize images that are larger than their specifications

                                       'js'                                 => array('load_delayed'       => true,                      // If set to true, the JS files will by default NOT be loaded in the <head> tag but at the end of the HTML <body> code so that the site will load faster. This may require some special site design to avoid problems though!
                                                                                     'internal_to_file'   => true),                     // If set to true, all html_script() output will be sent stored in external files which will be added automatically by html_load_js()

                                       'network'                            => array('enabled'            => false),                    // Use CDN network or not

                                       'normal'                             => array('js'                 => 'pub/js',                  // Location of js, CSS and image files for desktop pages
                                                                                     'css'                => 'pub/css',
                                                                                     'img'                => 'pub/img'),

                                       'mobile'                             => array('js'                 => 'pub/mobile/js',           // Location of js, CSS and image files for mobile pages
                                                                                     'css'                => 'pub/mobile/css',
                                                                                     'img'                => 'pub/mobile/img'),

                                       'path'                               => '',                                                      // Path component for location of CDN files. Useful for debugging multiple CDN servers

                                       'prefix'                             => '/pub/',                                                 // Prefix for all CDN objects, may be CDN server domain, for example

                                       'shared_key'                         => '');                                                     // Shared encryption key between site servers and CDN servers to send and receive encrypted messages


// Characterset
$_CONFIG['encoding']           = array('charset'                            => 'UTF-8',                                                 // The default character set for this website (Will be used in meta charset tag)
                                       'normalize'                          => null);                                                   // Normalize UTF8 strings with the specified form. Possible values are Normalizer::FORM_C | Normalizer::FORM_D | Normalizer::FORMKC | Normalizer::FORMKD Normalizer::NONE | Normalizer::OPTION_DEFAULT | null for default FORM_C

// Check disk configuration
$_CONFIG['check_disk']         = array('percentage'                         => 20,                                                      // The default minimum required available disk space for the filesystem for ROOT in %
                                       'bytes'                              => 0);                                                      // The default minimum required available disk space for the filesystem for ROOT in bytes

// CLI configuration
$_CONFIG['cli']                = array('timeout'                            => '30');                                                   // Default timeout for programs running on the CLI

// Client configuration
$_CONFIG['client']             = array('detect'                             => false);                                                  // If client detection should be performed. false if not, one of "full", "normal" or "lite" if detection should happen, and what type of detection

// Content configuration
$_CONFIG['content']            = array('autocreate'                         => false);                                                  // When using load_content(), if content is missing should it be created automatically? Normally, use "true" on development and test machines, "false" on production

//
$_CONFIG['copyright']          = array('name'                               => 'lasvegasstriptease.com',                                // Name to be displayed for the copyright
                                       'url'                                => 'https://lasvegasstriptease.com/copyright.html');        // URL used for the copyright

// Access-Control-Allow-Origin configuration. comma delimeted list of sites to allow with CORS
$_CONFIG['cors']               = array('origin'                             => '*.',
                                       'methods'                            => 'GET, POST',
                                       'headers'                            => '');

// Global data location configuration
$_CONFIG['data']               = array('global'                             => true); // Set to TRUE to enable auto detect

// Database connectors configuration
$_CONFIG['db']                 = array('default'                            => 'core',

                                       'core'                               => array('driver'           => 'mysql',                         // PDO Driver used to communicate with the database server. For now, only MySQL has been tested, no others have been used yet, use at your own discretion
                                                                                     'host'             => 'localhost',                     // Hostname for SQL server
                                                                                     'port'             => '',                              // If set, don't use the default 3306 port
                                                                                     'user'             => '',                              // Username to login to SQL server
                                                                                     'pass'             => '',                              // Password to login to SQL server
                                                                                     'db'               => '',                              // Name of core database on SQL server
                                                                                     'init'             => true,                            // If set to true, upon first query of the pageload, the SQL library will check if the database requires initialization
                                                                                     'autoincrement'    => 1,                               // Default autoincrement for all database tables (MySQL only)
                                                                                     'buffered'         => false,                           // WARNING, READ ALL!! Use buffered queries or not. See PHP documentation for more information. WARNING: Currently buffered queries appear to completely wreck this sytem, do NOT use!
                                                                                     'charset'          => 'utf8mb4',                       // Default character set for all database tables
                                                                                     'collate'          => 'utf8mb4_general_ci',            // Default collate set for all database tables
                                                                                     'limit_max'        => 10000,                           // Standard SQL allowed LIMIT specified in table displays, for example, to avoid displaying a table with a milion entries, for example
                                                                                     'mode'             => 'PIPES_AS_CONCAT,IGNORE_SPACE',  // Special mode options for MySQL server
                                                                                     'ssh_tunnel'       => array('required'    => false,    // SSH tunnel configuration. This should NOT be used for the core database!
                                                                                                                 'local_port'  => 3307,     // Port on the local machine to enter in the SSH tunnel
                                                                                                                 'remote_port' => 3306,     // MySQL server port on the remote server
                                                                                                                 'server'      => ''),      // SEO name of server registered in the servers table

                                                                                     'pdo_attributes'   => array(),                         // Special PDO otions. By default, try to use MySQLND with PDO::ATTR_EMULATE_PREPARES to avoid internal data type changes from int > string!
                                                                                     //'pdo_attributes'   => array(PDO::ATTR_EMULATE_PREPARES  => false,  // Special PDO otions. By default, try to use MySQLND with PDO::ATTR_EMULATE_PREPARES to avoid internal data type changes from int > string!
                                                                                     //                            PDO::ATTR_STRINGIFY_FETCHES => false, ),
                                                                                     'timezone'         => 'UTC'));                         // Default timezone to use

// Domain configuration
$_CONFIG['domain']             = 'auto';                                                                                                    // The base domain of this website. for example, "mywebsite.com",  "thisismine.com.mx", etc. If set to "auto" it will use $_SERVER[SERVER_NAME]

// Exec configuration
$_CONFIG['exec']               = array('path'                               => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/snap/bin',  // The default path used by safe_exec()
                                       'timeout'                            => 10);

// Feedback configuration
$_CONFIG['feedback']           = array('emails'                             => array('Capmega Support' => 'support@capmega.com'));

// File library configuration
$_CONFIG['file']                                                                = array('dir_mode'         => 0770,                                     // When the system creates directory, this sets what file mode it will have (Google unix file modes for more information)
                                                                                        'file_mode'        => 0660,                                     // When the system creates a file, this sets what file mode it will have (Google unix file modes for more information)
                                                                                        'target_path_size' => 4,                                        // When creating
                                                                                        'download'         => array('compression' => 'auto'));          // When downloading a file to a client, use compression or not. Either true, false, or "auto". In case of auto, the system will determine the mimetype of the file, and from there decide if compression is a good idea or not (jpeg, for example, might not be a good idea). Recommended value is "auto"

// Flash alert configuration
$_CONFIG['flash']              = array('type'                               => 'html',                                                      // The type of HTML flash message to use. Either "html" or "sweetalert"
                                       'html'                               => '<div class="flash:type :hidden">:message</div>');

//
$_CONFIG['formats']            = array('force1224'                          => '24',
                                       'date'                               => 'Ymd',
                                       'time'                               => 'YmdHis',
                                       'human_date'                         => 'd/m/Y',
                                       'human_time'                         => 'H:i:s',
                                       'human_datetime'                     => 'd/m/Y H:i:s',
                                       'human_nice_date'                    => 'l, j F Y');

// Init configuration
$_CONFIG['init']               = array('cli'                                => true,                                                        // Sets if system init can be executed by shell
                                       'http'                               => false);                                                      // Sets if system init can be executed by http (IMPORTANT: This is not supported yet!)

// JS configuration
$_CONFIG['js']                 = array('animate'                            => array('speed'              => 100));                         // Sets default speed for javascript animations

// jQuery UI configuration
$_CONFIG['jquery-ui']          = array('theme'                              => 'smoothness');                                               // Sets the default UI theme for jquery-ui

// Language
$_CONFIG['language']           = array('default'                            => 'en',                                                        // If www user has no language specified, this determines the default language. Either a 2 char language code (en, es, nl, ru, pr, etc) or "auto" to do GEOIP language detection
                                       'detect'                             => true,                                                        // Perform requested language auto detect
                                       'supported'                          => array('en'                 => 'English',                     // Associated array list of language_code => language_name of supported languages for this website
                                                                                     'es'                 => 'Español',                     // Associated array list of language_code => language_name of supported languages for this website
                                                                                     'nl'                 => 'Nederlands'));

// Locale configuration
$_CONFIG['locale']             = array(LC_ALL                               => ':LANGUAGE_:COUNTRY.UTF8',
                                       LC_COLLATE                           => null,
                                       LC_CTYPE                             => null,
                                       LC_MONETARY                          => null,
                                       LC_NUMERIC                           => null,
                                       LC_TIME                              => null,
                                       LC_MESSAGES                          => null);

// Location configuration
$_CONFIG['location']           = array('default_country'                    => 'US',
                                       'detect'                             => false);                                                      // Attempt auto location detect if current session doesn't have location information

// Log configuration
$_CONFIG['log']                = array('single'                             => true);                                                       // All file logs will go to one and the same file

// Logo configuration
$_CONFIG['logo']               = array('og'                                 => '',                                                          // Default open graph logo for the project
                                       'site'                               => '');                                                         // Default website logo for the project

// Mail configuration
$_CONFIG['mail']               = array('developers'                         => array());

// Maintenance configuration
$_CONFIG['maintenance']        = false;                                                                                                     // If set to true, the only page that will be displayed is the www/LANGUAGE/maintenance.php

// Memcached configuration. If NOT set to false, the memcached library will automatically be loaded!
$_CONFIG['memcached']          = array('servers'                            => array(array('localhost', 11211, 20)),                        // Array of multiple memcached servers. If set to false, no memcached will be done.
                                       'expire_time'                        => 86400,                                                       // Default memcached object expire time (after this time memcached will drop them automatically)
                                       'prefix'                             => PROJECT.'-',                                                 // Default memcached object key prefix (in case multiple projects use the same memcached server)
                                       'namespaces'                         => true);                                                       // Use namespaces to store the data. This will require extra lookups on memcached to determine namespaces contents, but allows for more flexibility

//Meta configuration
$_CONFIG['meta']               = array('author'                             => '');                                                         // Set default meta tags for this site which may be overruled by parameters for the function html_header(). See libs/html.php

// Mobile configuration
$_CONFIG['mobile']             = array('enabled'                            => true,                                                        // If disabled, treat every device as a normal desktop device, no mobile detection will be done
                                       'force'                              => false,                                                       // Treat every device as if it is a mobile device
                                       'auto_redirect'                      => true,                                                        // If set to true, the first session page load will automatically redirect to the mobile version of the site
                                       'tablets'                            => false,                                                       // Treat tablets as mobile devices
                                       'viewport'                           => 'width=device-width, initial-scale=1, maximum-scale=1');     // The <meta> viewport tag used for this site

// Name of the website
$_CONFIG['name']               = 'base';

// Do not index this site. DANGEROUS! WILL DESTROY SEO! Use for intranet websites, for example.
$_CONFIG['noindex']            = false;                                                                                                     // If set to true, the entire website will not be indexed at all by google. Use for intranet websites


// Paging configuration
$_CONFIG['paging']             = array('limit'                              => 50,                                                          // The maximum amount of items shown per page
                                       'show_pages'                         => 5,                                                           // The maximum amount of pages show, should always be an odd number, or an exception will be thrown!
                                       'prev_next'                          => true,                                                        // Show previous - next links
                                       'first_last'                         => true,                                                        // Show first - last links
                                       'hide_first'                         => true,                                                        // Hide first number (number 1) in URL, useful for links like all.html, all2.html, etc
                                       'hide_single'                        => true,                                                        // Hide pager if there is only a single page
                                       'hide_ends'                          => true,                                                        // Hide the "first" and "last" options
                                       'list'                               => array(  10 => tr('Show 10 entries'),
                                                                                       20 => tr('Show 20 entries'),
                                                                                       50 => tr('Show 50 entries'),
                                                                                      100 => tr('Show 100 entries'),
                                                                                      500 => tr('Show 500 entries')));

// Prefetch
$_CONFIG['prefetch']           = array('dns'                                => array('facebook.com',
                                                                                     'twitter.com'),

                                       'files'                              => array());

// Is this a production environment?
$_CONFIG['production']         = true;

// Redirects configuration (This ususally would not require changes unless you want to have other file names for certain actions like signin, etc)
$_CONFIG['redirects']          = array('auto'                               => 'get',                                                       // Auto redirects (usually because of user or right required) done by "session" or "get"
                                                                               'index'            => 'index.html',                          // What is the default index page for this site
                                                                               'accessdenied'     => '403',                                 // Usually won't redirect, but just show
                                                                               'signin'           => 'signin.html',                         // What is the default signin page for this site
                                                                               'lock'             => 'lock.html',                           // What is the default lock page for this site
                                                                               'aftersignin'      => 'index.html',                          // Where will the site redirect to by default after a signin?
                                                                               'aftersignout'     => 'index.html');


// Real ROOT path
$_CONFIG['root']               = '';                                                                                                        // Real ROOT path of the entire project (in case phoundation is installed a sub directory of another framework). Used for deployment, code script, etc. Leave empty if Phoundation is the only framework used for your website

// Routing configuration
$_CONFIG['route']             = array('static'                              => true,                                                        // If set to true, support for static routes for IPs is added. This is useful to auto block IP's with 404's, or block them completely after the routing table detected fishy actions

                                      'known_hacks'   => array(array('regex' => '/cgi-bin/i',                                               // Requesting cgi-bin directories is bad
                                                                     'flags' => 'B,H,L,S'),

                                                               array('regex' => '/(?:\.well-known|acme-challenge)\//i',                     // Requesting cgi-bin directories is bad
                                                                     'flags' => 'B,H,L,S'),

                                                               array('regex' => '/wp-(?:admin|login|content|file-manager)/i',               // Known wordpress file sections
                                                                     'flags' => 'B,H,L,S'),

                                                               array('regex' => '/(?:www|data|public|init|config|scripts|libs)\//i',        // Directories in the phoundation structure or structure of other frameworks like laravel that should never be requested by anybody
                                                                     'flags' => 'B,H,L,S'),

                                                               array('regex' => '/<\??php/i',                                               // URL's containing <?php or <php
                                                                     'flags' => 'B,H,L,S'),

                                                              array('regex' => '/die\(/i',                                                  // URL's containing die(
                                                                    'flags' => 'B,H,L,S'),

                                                              array('regex' => '/execute/i',                                                // URL's containing execute
                                                                    'flags' => 'B,H,L,S'),

                                                              array('regex' => '/xdebug/i',                                                 // URL's containing xdebug
                                                                    'flags' => 'B,H,L,S'),

                                                              array('regex' => '/phpstorm/i',                                               // URL's containing phpstorm
                                                                    'flags' => 'B,H,L,S'),

                                                              array('regex' => '/call_user_func/i',                                         // URL's containing call_user_func
                                                                    'flags' => 'B,H,L,S'),

                                                              array('regex' => '/\/eval/i',                                                 // URL's containing /eval
                                                                    'flags' => 'B,H,L,S'),

                                                              array('regex' => '/invokefunction/i',                                         // URL's containing invokefunction
                                                                    'flags' => 'B,H,L,S'),

                                                              array('regex' => '/jsonws/i',                                                 // URL's containing jsonws
                                                                    'flags' => 'B,H,L,S'),

                                                              array('regex' => '/phpunit/i',                                                // URL's containing phpunit
                                                                    'flags' => 'B,H,L,S'),

                                                              array('regex' => '/\.\./i',                                                   // URL's containing ..
                                                                    'flags' => 'B,H,L,S'),

                                                               array('regex' => '/C=S;O=A/i',                                                // HTTP query variables that apparently cause issues on some systems
                                                                     'flags' => 'B,H,L,S,M')),

                                      'languages_map' => null);


// Security configuration
$_CONFIG['security']           = array('signin'                             => array('save_password'    => true,                            // Allow the browser client to save the passwords. If set to false, different form names will be used to stop browsers from saving passwords
                                                                                     'ip_lock'          => false,                           // Either "false", "true" or number n (which makes it lock to users with the right ip_lock), or "ip address" or array("ip address", "ip address", ...). If specified as true, only 1 IP will be allowed. If specified as number N, up to N IP addresses will be allowed. If specified as "ip address", only that IP address will be allowed. If specified as array("ip address", ...) all IP addresses in that array will be allowed
                                                                                     'destroy_session'  => false,                           // Either "false", "true" or number n (which makes it lock to users with the right ip_lock), or "ip address" or array("ip address", "ip address", ...). If specified as true, only 1 IP will be allowed. If specified as number N, up to N IP addresses will be allowed. If specified as "ip address", only that IP address will be allowed. If specified as array("ip address", ...) all IP addresses in that array will be allowed
                                                                                     'two_factor'       => false),                          // Either "false" or a valid twilio "from" phone number

                                       'authentication'                     => array('captcha_failures' => false,                           // Authentication failures until re-captcha is required. If 0, recaptcha will be required with every authentication. If set to FALSE, captcha will be disabled completely
                                                                                     'auto_lock_fails'  => 6,                               // Upon N authentication failures, auto lock user accounts. Set to 0 to disable
                                                                                     'auto_lock_time'   => 60),                             // Upon user account auto locking, keep the account locked for N seconds. Set to 0 to disable

                                       'passwords'                          => array('test'             => false,                           // Test new user password strength?
                                                                                     'hash'             => 'sha256',                        // What hash algorithm will we use to store the passwords?
                                                                                     'usemeta'          => true,                            // Add used hash as meta data to the password when storing them so we know what hash was used.
                                                                                     'useseed'          => true,                            // Use the SEED constant to calculate passwords
                                                                                     'unique_updates'   => 3,                               // Passwords cannot be updated to the same password for minimum N times
                                                                                     'unique_days'      => 30),                             // Passwords cannot be updated to the same password for minimum N days

                                       'url_cloaking'                       => array('enabled'          => false,                           // Enable the URL cloaking system if set to true
                                                                                     'strict'           => true,                            // If set to true, each cloaked URL can only be used by the user that created it
                                                                                     'interval'         => 1,                               // The chance in % that the url_cloaking table will be cleaned at a page load
                                                                                     'expires'          => 86400),                          // The time until a cloaked URL will be removed from the URL cloaking table and no longer available

                                       'user'                               => 'apache',                                                    //
                                       'group'                              => 'apache',                                                    //
                                       'umask'                              =>  0007,                                                       //
                                       'expose_php'                         => false,                                                       // If false, will hide the X-Powered-By PHP header. If true, will leave the header as is. If set to any other value, will send that value as X-Powered-By value
                                       'seed'                               => '%T"$#HET&UJHRT87',                                          // SEED for generating codes
                                       'signature'                          => true,                                                        // If set to true, add a phoundation signature in HTTP headers and on certain pages.

                                       'csrf'                               => array('enabled'          => 'force',                         // CSRF detection configuration. true | false | "force". Force will forcibly check every POST on CSRF
                                                                                     'buffer_size'      => 10,                              // The amount of server side CSRF keys that are being kept. With more keys, more pages can be run in parrallel
                                                                                     'timeout'          => 0));                             // Timeout after page generation, where @ POST time the CSRF check will fail. Use 0 to disable


// Sessions
$_CONFIG['sessions']           = array('enabled'                            => true,                                                        // Have a system with sessions. If enabled, the system will use cookies
                                       'lifetime'                           => 86400,                                                       // Total time that a session may exist until the user has to login again
                                       'timeout'                            => 86400,                                                       // Time between pageloads that, when passed, will cause the session to be closed
                                       'http'                               => true,                                                        // Sets if cookies can be sent over other protocols than HTTP. See https://secure.php.net/manual/en/session.configuration.php#ini.session.cookie_httponly
                                       'secure'                             => true,                                                        // Sets if cookies can only be sent over secure connections. See https://secure.php.net/manual/en/session.configuration.php#ini.session.cookie_secure
                                       'same_site'                          => 'Strict',                                                    // false | Lax | Strict : Sets if cookiets can be sent cross domain by browser. See https://secure.php.net/manual/en/session.configuration.php#ini.session.cookie_samesite
                                       'strict'                             => true,                                                        // Forces session.use_strict_mode to the specified value. Recommended TRUE for session security! See https://secure.php.net/manual/en/session.configuration.php#ini.session.use_strict_mode
                                       'regenerate_id'                      => 600,                                                         // Time required to regenerate the session id, used to mitigate session fixation attacks. MUST BE LOWER THAN $_CONFIG[session][lifetime]!
                                       'check_referrer'                     => true,                                                        // If set to true, the referrer must contain the domain name
                                       'handler'                            => false,                                                       // false | mm | mc | sql Use the default PHP session manager, shared memory manager (mm), memcached (mc) or sql_sessions library to manage sessions
                                       'euro_cookies'                       => false,                                                       // If set to true, all european countries will see a "This site uses cookies" warning before cookies are being sent
                                       'domain'                             => 'auto',                                                      // If set to "auto", will apply to current domain. If set to ".auto", will apply to current domain plus sub domains. If set to specific-domain, or .specific-domain then it will do the same but for the specific-domain
                                       'path'                               => '/',                                                         // The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
                                       'cookie_name'                        => 'phoundation',                                               // The name for the cookie

                                       'extended'                           => array('age'           => 2592000,                            //
                                                                                     'clear'         => true),                              //

                                       'signin'                             => array('force'         => false,                              //
                                                                                     'allow_next'    => true,                               //
                                                                                     'redirect'      => 'index.php'));                      //

// Shutdown configuration
$_CONFIG['shutdown']           = array('check-disk' => array('interval' => 2,                                                               // Execute this function every INTERVAL / 100 times
                                                             'library'  => 'check-disk',                                                    // To execute the function, load this library
                                                             'function' => 'check_disk'),                                                   // The function to execute

                                       'log-rotate' => array('interval' => 2,
                                                             'library'  => 'log',
                                                             'function' => 'log_rotate'));

// Sync configuration.
$_CONFIG['sync']               = array();                                                                                                   //

// Sync configuration.
$_CONFIG['statistics']         = array('enabled'                            => true);                                                       //

// Timezone configuration. See http://www.php.net/manual/en/timezones.php for more info
$_CONFIG['timezone']           = array('display'                            => 'America/Mexico_City',                                       // Default timezone to be used to display date/time
                                       'system'                             => 'UTC');                                                      // Default system timezone to be used when dates are stored

// Default title configuration
$_CONFIG['title']              = 'Base';                                                                                                    //

// What webservice this is.
$_CONFIG['type']               = 'core';                                                                                                    // core, api, cdn

// User configuration
$_CONFIG['users']              = array('type_filter'                        => null,
                                       'unique_nicknames'                   => true,
                                       'password_minumum_strength'          => 4,
                                       'duplicate_reference_codes'          => false);

$_CONFIG['whitelabels']        = false;                                                                                                     // Either false (No whitelabel domains, only the normal site FQDN allowed), "list" (only default and registered FQDNs allowed), "sub" (only default FQDN and its sub domains allowed), "all" (All domains allowed), or the specific FQDN that is allowed

// Root URL of the website
$_CONFIG['url_prefix']         = '';                                                                                                        //

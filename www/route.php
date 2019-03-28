<?php
/*
 * Routing table script
 *
 * This script contains the routing table, which routes requested URL's to specific PHP pages
 *
 * The route() call requires 3 arguments; $regex, $target, and $flags.
 *
 * The first argument is the regular expression that will match the URL you wish to route to a page. This regular expression may capture variables
 *
 * The second argument is the page you wish to execute and the variables that should be sent to it. If your regular expression captured variables, you may use these variables here. If the page name itself is a variable, then route() will try to find that page, and execute it if it exists
 *
 * The third argument is a list (CSV string or array) with flags. Current allowed flags are:
 * Q Allow queries to pass through. If NOT specified, and the URL contains queries, the URL will NOT match!
 * R301 Redirect to the specified page argument using HTTP 301
 * R302 Redirect to the specified page argument using HTTP 302
 * P The request must be POST to match
 * G The request must be GET to match
 * C Use URL cloaking. A cloaked URL is basically a random string that the route() function can look up in the `cloak` table. domain() and its related functions will generate these URL's automatically. See the "url" library, and domain() and related functions for more information
 *
 * The $verbose and $veryverbose variables here are to set the system in VERBOSE or VERYVERBOSE mode, but ONLY if the system runs in debug mode. The former will add extra log output in the data/log files, the latter will add LOADS of extra log data in the data/log files, so please use with care and only if you cannot resolve the problem
 *
 * The translation map helps route() to detect URL's where the language is native. For example; http://phoundation.org/about.html and http://phoundation.org/nosotros.html should both route to about.php, and maybe you wish to add multiple languages for this. The routing table basically says what static words should be translated to their native language counterparts. The mapped_domain() function use this table as well when generating URL's. See mapped_domain() for more information
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package route
 * @see route_404()
 * @see mapped_domain()
 * @table: `route`
 * @version 1.27.0: Added function and documentation
 * @version 2.0.7: Now uses route_404() to display 404 pages
 * @version 2.5.63: Improved documentation
 * @example
 * code
 * route('/\//'                                            , 'index'                                , '');     // This would NOT allow queries, and the match would fail
 * route('/\//'                                            , 'index'                                , 'Q');    // This would allow queries
 * route('/^([a-z]{2})\/page\/([a-z-]+)?(?:-(\d+))?.html$/', '$1/$2.php?page=$3'                    , 'Q');    // This would map a URL like en/page/users-4.html to ROOT/en/users.php?page=4 while also allowing queries to be passed as well.
 * route(''                                                , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/', 'R301'); // This will HTTP 301 redirect the user to a page with the same protocol, same domain, but the language that their browser requested. So for example, http://domain.com with HTTP header "accept-language:en" would HTTP 301 redirect to http://domain.com/en/
 * /code
 *
 * The following example code will set a language route map where the matched word "from" would be translated to "to" and "foor" to "bar" for the language "es"
 *
 * code
 * route('map', array('language' => 2,
 *                    'es'       => array('servicios'    => 'services',
 *                                        'portafolio'   => 'portfolio'),
 *                    'nl'       => array('diensten'     => 'services',
 *                                        'portefeuille' => 'portfolio')));
 * route('/\//', 'index')
 * /code
 *
 * @example Setup URL translations map. In this example, URL's with /es/ with the word "conferencias" would map to the word "conferences", etc.
 * code
 * route('map', array('es' => array('conferencias' => 'conferences',
 *                                  'portafolio'   => 'portfolio',
 *                                  'servicios'    => 'services',
 *                                  'nosotros'     => 'about'),
 *
 *                   'nl' => array('conferenties' => 'conferences',
 *                                  'portefeuille' => 'portfolio',
 *                                  'diensten'     => 'services',
 *                                  'over-ons'     => 'about')));
 * /code
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package route
 */
$verbose     = true;
$veryverbose = true;
require_once(__DIR__.'/en/libs/route.php');

/*
 * Setup URL translations map
 */
route('map', array('es' => array('conferencias' => 'conferences',
                                 'portafolio'   => 'portfolio',
                                 'servicios'    => 'services',
                                 'nosotros'     => 'about'),

                   'nl' => array('conferenties' => 'conferences',
                                 'portefeuille' => 'portfolio',
                                 'diensten'     => 'services',
                                 'over-ons'     => 'about')));

route('/^([a-z]{2})\/$/'                                    , '$1/index.php'                                         , '');                 // Show index page
route('/^([a-z]{2})\/([-a-z]+).html$/'                      , '$1/$2.php'                                            , '');                 // Show pages with page name in URL
route('/^([a-z]{2})\/(?!admin)([a-z]+)\/([a-z0-9-]+).html$/', '$1/$2.php?item=$3'                                    , '');                 // Show pages with page/section name in URL
route(''                                                    , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/'                , 'R301');             // Main page has to redirect to a language page
route('/^([a-z]{2})\/(admin\/)?ajax\/([a-z\/]+).php$/'      , '$1/$2ajax/$3.php'                                     , 'Q');                // Redirect to admin ajax pages
route('/^([a-z]{2})\/admin\/?$/'                            , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/admin/index.html', 'R301');             // /en/admin/ has to go to /en/admin/index.html
route('/^([a-z]{2})\/admin\/([a-z-]+).html$/'               , '$1/admin/$2.php'                                      , 'Q');                // Show admin pages with page name in URL
route('/^admin\/?$/'                                        , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/admin/index.html', 'R301');             // /admin/ has to go to /en/admin/index.html
?>

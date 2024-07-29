<?php

/**
 * Routing table script
 *
 * This script contains the routing table, which routes requested URL's to specific PHP pages
 *
 * The first argument is the PERL compatible regular expression that will match the URL you wish to route to a
 * page.
 * Note that this must be a FULL regular expression with opening and closing tags. / is recommended for these tags,
 * but not required. See https://www.php.net/manual/en/function.preg-match.php for more information about PERL
 * compatible regular expressions. This regular expression may capture variables which then can be used in the
 * target as $1, $2 for the first and second variable respectitively. Regular expression flags like i (case
 * insensitive matches), u (unicode matches), etc. may be added after the trailing / of this variable
 * *
 * The second argument is the page you wish to execute and the variables that should be sent to it. If your regular
 * expression captured variables, you may use these variables here. If the page name itself is a variable, then
 * Route::add() will try to find that page, and execute it if it exists
 * *
 * The third argument is a list (CSV string or array) with flags. Current allowed flags are:
 * A                Process the target as an attachement (i.e. Send the file so that the browser client can
 * download
 *                  it)
 * B                Block. Return absolutely nothing
 * C                Use URL cloaking. A cloaked URL is basically a random string that the Route::add() function can
 *                  look up in the `cloak` table. domain() and its related functions will generate these URL's
 *                  automatically. See the "url" library, and domain() and related functions for more information
 * D                Add HTTP_HOST to the REQUEST_URI before applying the match
 * G                The request must be GET to match
 * H                If the routing rule matches, the router will add a *POSSIBLE HACK ATTEMPT DETECTED* log entry
 *                  for later processing
 * L                Disable language map requirements for this specific URL (Use this with non language URLs on a
 *                  multi lingual site)
 * P                The request must be POST to match
 * M                Add queries into the REQUEST_URI before applying the match, autmatically implies Q
 * N                Do not check for permanent routing rules
 * Q                Allow queries to pass through. If NOT specified, and the URL contains queries, the URL will NOT
 *                  match!
 * QKEY;KEY=ACTION  Is a ; separated string containing query keys that are allowed, and if specified, what action
 *                  must be taken when encountered
 * R301             Redirect to the specified page argument using HTTP 301
 * R302             Redirect to the specified page argument using HTTP 302
 * S$SECONDS$       Store the specified rule for this IP and apply it for $SECONDS$ number of seconds. $SECONDS$ is
 *                  optional, and defaults to 86400 seconds (1 day). This works well to auto 404 IP's that are
 *                  doing
 *                  naughty things for at least a day
 * T$TEMPLATE$      Use the specified template instead of the current template for this try
 * X$PATHS$         Restrict access to the specified dot-comma separated $PATHS$ list. $PATHS is optional and
 *                  defaults to DIRECTORY_WEB, DIRECTORY_DATA .'content/downloads'
 * Z$RIGHT$[$PAGE$] Requires that the current session user has the specified right, or $PAGE$ will be shown, with
 *                  $PAGE$ defaulting to system/403. Multiple Z flags may be specified
 * *
 * The $Debug::enabled() and $Debug::enabled() variables here are to set the system in Debug::enabled() or
 * Debug::enabled() mode, but ONLY if the system runs in debug mode. The former will add extra log output in the
 * data/log files, the latter will add LOADS of extra log data in the data/log files, so please use with care and
 * only if you cannot resolve the problem
 * *
 * Once all Route::add() calls have passed without result, the system will shut down. The shutdown() call will then
 * automatically execute Request::executeSystem() which will display the 404 page
 * *
 * To use translation mapping, first set the language map using Route::map()
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 * @see       Route::404()
 * @see       Route::mappedDomain()
 * @table     : `route`
 * @version   1.27.0: Added function and documentation
 * @version   2.0.7: Now uses route_404() to display 404 pages
 * @version   2.5.63: Improved documentation
 * @example
 * code
 * route('/\//'                                            , 'index'                                , '');     // This
 * would NOT allow queries, and the match would fail route('/\//'                                            , 'index'
 *                               , 'Q');    // This would allow queries
 *                               route('/^([a-z]{2})\/page\/([a-z-]+)?(?:-(\d+))?.html$/', '$1/$2.php?page=$3'
 *                                         , 'Q');    // This would map a URL like en/page/users-4.html to
 *                                         PATH_ROOT/en/users.php?page=4 while also allowing queries to be passed as
 *                                         well. Route(''                                                ,
 *                                         ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/', 'R301'); // This will HTTP 301
 *                                         redirect the user to a page with the same protocol, same domain, but the
 *                                         language that their browser requested. So for example, http://domain.com
 *                                         with HTTP header "accept-language:en" would HTTP 301 redirect to
 *                                         http://domain.com/en/
 * /code
 *
 * The following example code will set a language route map where the matched word "from" would be translated to "to"
 * and "foor" to "bar" for the language "es"
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
 * @example   Setup URL translations map. In this example, URL's with /es/ with the word "conferencias" would map to
 *            the word "conferences", etc. code route('map', array('es' => array('conferencias' => 'conferences',
 *                                  'portafolio'   => 'portfolio',
 *                                  'servicios'    => 'services',
 *                                  'nosotros'     => 'about'),
 *
 *                   'Nl' => array('conferenties' => 'conferences',
 *                                  'portefeuille' => 'portfolio',
 *                                  'diensten'     => 'services',
 *                                  'over-ons'     => 'about')));
 * /code
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   route
 */

declare(strict_types=1);

use Phoundation\Web\Routing\Map;
use Phoundation\Web\Routing\Route;
use Phoundation\Web\Routing\RoutingParameters;
use Templates\Phoundation\Mdb\Mdb;

require('../../../../vendor/autoload.php');


// Setup URL translations map
Route::getMapping()->add('/^(\w{2})\//',
                         Map::new('en', null),
                         Map::new('fr', [
                             'feuilles-de-temps' => 'timesheet',
                         ]),
                         Map::new('nl', [
                             'rooster' => 'timesheet',
                         ])
);


// Set routing parameters to be applied for the various page types
Route::getParametersObject()
     ->add(RoutingParameters::new('/^\w{2}\//') // Routing parameters for most pages
                            ->setTemplate(Mdb::class)
                            ->setRootUrl(':PROTOCOL://:DOMAIN/:LANGUAGE/')
                            ->setRightsExceptions('sign-in.php,sign-out.php')
                            ->setRights('mmb'))
     ->add(RoutingParameters::new() // Routing parameters for default english system pages
                            ->setSystemPagesOnly(true)
                            ->setTemplate(Mdb::class)
                            ->setRootDirectory('pages/'));


// Apply routing rules
Route::try('/^(\w{2})\/([a-z0-9-\/]+)\/([a-z0-9-]+)\+([0-9a-z-]+)?\.html$/', 'pages/$2/$3.php?id=$4');            // Show the requested existing entry page
Route::try('/^(\w{2})\/([a-z0-9-\/]+)\/([a-z0-9-]+)\.html$/', 'pages/$2/$3.php');                                 // Show the requested new entry page
Route::try('/^(\w{2})\/([a-z0-9-]+)\.html$/', 'pages/$2.php');                                                    // Show the requested page
Route::try('/^(\w{2})\/ajax\/(.+?)\.json$/', 'ajax/$2.php', 'Q');                                                 // Execute the requested AJAX page
Route::try('/^(\w{2})\/timesheets\/(\d{4}\/\d{2}\/\d{2})\.html$/', 'pages/timesheets/day.php?date=$2');           // Timesheet page
Route::try('/^(\w{2})\/?$/', '/index.html', 'R301');                                                              // Redirect to index page
Route::try('/^$/', '/index.html', 'R301');         // Redirect to index page
//Route::try('/^(\w{2})\/sso\/([a-z0-9-]+).html/', 'pages/sso/$1.php', 'Q'); // SAML SSO sign-on


//// System files / downloadable files
//Route::try('/(.+?(?:xml|txt))$/'                                 , '$1'                                                   , '');                 // System files like sitemap.xml, robot.txt, etc.
//Route::try('/\/files\/(.+)$/'                                    , '$1'                                                   , 'A');                // Downloadable files

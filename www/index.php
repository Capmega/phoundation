<?php

use Phoundation\Web\Routing\Route;
use Phoundation\Web\Routing\RoutingParameters;
use Templates\AdminLte\AdminLte;
use Templates\Mdb\Mdb;


/**
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
 * A                Process the target as an attachement (i.e. Send the file so that the browser client can download it)
 * B                Block. Return absolutely nothing
 * C                Use URL cloaking. A cloaked URL is basically a random string that the route() function can look up in the `cloak` table. domain() and its related functions will generate these URL's automatically. See the "url" library, and domain() and related functions for more information
 * D                Add HTTP_HOST to the REQUEST_URI before applying the match
 * G                The request must be GET to match
 * H                If the routing rule matches, the router will add a *POSSIBLE HACK ATTEMPT DETECTED* log entry for later processing
 * L                Disable language map requirements for this specific URL (Use this with non language URLs on a multi lingual site)
 * P                The request must be POST to match
 * M                Add queries into the REQUEST_URI before applying the match, autmatically implies Q
 * N                Do not check for permanent routing rules
 * Q                Allow queries to pass through. If NOT specified, and the URL contains queries, the URL will NOT match!
 * QKEY;KEY=ACTION  Is a ; separated string containing query keys that are allowed, and if specified, what action must be taken when encountered
 * R301             Redirect to the specified page argument using HTTP 301
 * R302             Redirect to the specified page argument using HTTP 302
 * S$SECONDS$       Store the specified rule for this IP and apply it for $SECONDS$ amount of seconds. $SECONDS$ is optional, and defaults to 86400 seconds (1 day). This works well to auto 404 IP's that are doing naughty things for at least a day
 * X$PATHS$         Restrict access to the specified dot-comma separated $PATHS$ list. $PATHS is optional and defaults to PATH_ROOT.'www,'.PATH_ROOT.'data/content/downloads'
 *
 * The translation map helps route() to detect URL's where the language is native. For example; http://phoundation.org/about.html and http://phoundation.org/nosotros.html should both route to about.php, and maybe you wish to add multiple languages for this. The routing table basically says what static words should be translated to their native language counterparts. The mapped_domain() function use this table as well when generating URL's. See mapped_domain() for more information
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package route
 * @see Route::404()
 * @see Route::mappedDomain()
 * @table: `route`
 * @version 1.27.0: Added function and documentation
 * @version 2.0.7: Now uses route_404() to display 404 pages
 * @version 2.5.63: Improved documentation
 * @example
 * code
 * route('/\//'                                            , 'index'                                , '');     // This would NOT allow queries, and the match would fail
 * route('/\//'                                            , 'index'                                , 'Q');    // This would allow queries
 * route('/^([a-z]{2})\/page\/([a-z-]+)?(?:-(\d+))?.html$/', '$1/$2.php?page=$3'                    , 'Q');    // This would map a URL like en/page/users-4.html to PATH_ROOT/en/users.php?page=4 while also allowing queries to be passed as well.
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
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package route
 */
require('../vendor/autoload.php');



//// Setup URL translations map
//Route::mapUrl('es', [
//    'conferencias' => 'conferences',
//    'portafolio'   => 'portfolio',
//    'servicios'    => 'services',
//    'nosotros'     => 'about'
//]);
//
//Route::mapUrl('nl', [
//    'conferenties' => 'conferences',
//    'portefeuille' => 'portfolio',
//    'diensten'     => 'services',
//    'over-ons'     => 'about'
//]);


// Set routing parameters to be applied for the various page types
Route::parameters()

   ->add(RoutingParameters::new() // Routing parameters for pages
        ->setPattern('/^\w{2}\/admin//')
        ->setRootUrl('http://:DOMAIN/:LANGUAGE/admin/')
        ->setRequirePathRights('pages/')
        ->setRights('phoundation')
        ->setRightsExceptions('sign-in.php,sign-out.php')
        ->setTemplate(AdminLte::class))

    ->add(RoutingParameters::new() // Routing parameters for system pages
        ->setPattern('/^(\w{2})\//')
        ->setTemplate(AdminLte::class)
        ->setRootPath('$1/pages/')
        ->setRootUrl('http://:DOMAIN/:LANGUAGE/')
        ->setSystemPagesOnly(true))

    ->add(RoutingParameters::new() // Routing parameters for default english system pages
        ->setTemplate(Mdb::class)
        ->setRootPath('en/pages/')
        ->setSystemPagesOnly(true));


// AdminLte based admin page routes
Route::try('/^(\w{2})\/ajax\/(.+?).html$/'       , '/$1/ajax/$2.php');           // Execute the requested AJAX page
Route::try('/^(\w{2})\/(.+?)\/(.+?)-(.+?).html$/', '/$1/pages/$2/$3.php?id=$4'); // Show the requested form page
Route::try('/^(\w{2})\/(.+?).html$/'             , '/$1/pages/$2.php');          // Show the requested table page
Route::try('/^(\w{2})\/?$/'                      , '/index.html', 'R301'); // Redirect to admin index page
Route::try('/^$/'                                , '/index.html', 'R301'); // Redirect to admin index page


// System files / downloadable files
Route::try('/(.+?(?:xml|txt))$/'                                 , '$1'                                                   , '');                 // System files like sitemap.xml, robot.txt, etc.
Route::try('/\/files\/(.+)$/'                                    , '$1'                                                   , 'A');                // Downloadable files

<?php
/*
 * URL to page routing script
 *
 * Order routes by expected traffic, rules with most traffic should be processed first
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Routing
 * @package route
 */
//$verbose = true;
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

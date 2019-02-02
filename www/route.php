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

route('/^[a-z]{2}\/$/'                    , 'http' , 'index');                                                                   // Show index page
route('/^[a-z]{2}\/ajax\/([a-z\/]+).php$/', 'ajax' , '$1', 'Q');                                                                 // Show pages with page name in URL
route(''                                  , ''     , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/', 'R301');                           // Main page has to redirect to a language page
route('/^[a-z]{2}\/admin\/?$/'            , ''     , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/admin/index.html', 'R301');           // /en/admin/ has to go to /en/admin/index.html
route('/^[a-z]{2}\/admin\/([a-z]+).html$/', 'admin', '$1', 'Q');                                                                 // Show pages with page name in URL
route('/^admin\/?$/'                      , ''     , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/admin/index.html', 'R301');           // /admin/ has to go to /en/admin/index.html
?>

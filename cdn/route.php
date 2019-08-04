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
 * Everything 404s!
 */
route('.*'                                                                      , 'system/404.php', '');                 // Show pages with page name in URL

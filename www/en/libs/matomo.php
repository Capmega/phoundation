<?php
/*
 * Matomo library
 *
 * This is a front-end for the matomo analytics system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Returns the necessary javascript for adding a google analytics code to a page
 * This code, however, will load the script from our own servers, avoiding extra
 * DNS lookups, avoiding shitty google caching headers which google is
 * complaining about, etc
 *
 * @obsolete analytics_matomo();
 */
function matomo_get_code($sites_id) {
    try{
        load_libs('analytics');
        return analytics_matomo($sites_id);

    }catch(Exception $e) {
        throw new CoreException('matomo_get_code(): Failed', $e);
    }
}
?>

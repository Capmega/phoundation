<?php
/*
 * URL library
 *
 * This library contains various functions to manage URLs
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package url
 */



/*
 * Cloak the specified URL.
 *
 * URL cloaking is nothing more than replacing a full URL (with query) with a random string. This function will register the requested URL
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package url
 * @see url_decloak()
 * @version 2.4.4: Added function and documentation
 *
 * @param string the URL to be cloaked
 * @return string The cloaked URL
 */
function url_cloak($url) {
    try{
        $cloak = sql_get('SELECT `cloak`

                          FROM   `url_cloaks`

                          WHERE  `url`       = :url
                          AND    `createdby` = :createdby',

                          true, array(':url'       => $url,
                                      ':createdby' => isset_get($_SESSION['user']['id'])));

        if ($cloak) {
            /*
             * Found cloaking URL, update the createdon time so that it won't
             * exipre too soon
             */
            sql_query('UPDATE `url_cloaks` SET `createdon` = NOW() WHERE `url` = :url', array(':url' => $url));
            return $cloak;
        }

        $cloak = str_random(32);

        sql_query('INSERT INTO `url_cloaks` (`createdby`, `url`, `cloak`)
                   VALUES                   (:createdby , :url , :cloak )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':cloak'     => $cloak,
                         ':url'       => $url));

        return $cloak;

    }catch(Exception $e) {
        throw new CoreException('url_cloak(): Failed', $e);
    }
}



/*
 * Uncloak the specified URL.
 *
 * URL cloaking is nothing more than
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package url
 * @see url_decloak()
 * @version 2.4.4: Added function and documentation
 *
 * @param string the URL to be cloaked
 * @return string The cloaked URL
 */
function url_decloak($cloak) {
    global $_CONFIG, $core;

    try{
        $data = sql_get('SELECT `createdby`, `url` FROM `url_cloaks` WHERE `cloak` = :cloak', array(':cloak' => $cloak));

        if (mt_rand(0, 100) <= $_CONFIG['security']['url_cloaking']['interval']) {
            url_cloak_cleanup();
        }

        if ($data) {
            $core->register['url_cloak_users_id'] = $data['createdby'];
            return $data['url'];
        }

        return '';

    }catch(Exception $e) {
        throw new CoreException('url_decloak(): Failed', $e);
    }
}



/*
 * Cleanup the url_cloaks table
 *
 * Since the URL cloaking table might fill up over time with new entries, this function will be periodically executed by url_decloak() to cleanup the table
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package url
 * @see url_decloak()
 * @version 2.4.4: Added function and documentation
 *
 * @return natural The amount of expired entries removed from the `url_cloaks` table
 */
function url_cloak_cleanup() {
    global $_CONFIG;

    try{
        log_console(tr('Cleaning up `url_cloaks` table'), 'VERBOSE/cyan');

        $r = sql_query('DELETE FROM `url_cloaks` WHERE `createdon` < DATE_SUB(NOW(), INTERVAL '.$_CONFIG['security']['url_cloaking']['expires'].' SECOND);');

        log_console(tr('Removed ":count" expired entries from the `url_cloaks` table', array(':count' => $r->rowCount())), 'green');

        return $r->rowCount();

    }catch(Exception $e) {
        throw new CoreException('url_cloak_cleanup(): Failed', $e);
    }
}
?>

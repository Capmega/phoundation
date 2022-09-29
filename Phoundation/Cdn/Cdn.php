<?php

/**
 * Class Cdn
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Cdn
{
    /*
     * Adds the required amount of copies of the specified file to random CDN servers
     */
    function cdn_add_files($files, $section = 'pub', $group = null, $delete = true)
    {
        global $_CONFIG;

        try {
            if (!$_CONFIG['cdn']['enabled']) {
                return false;
            }

            log_file(tr('cdn_add_files(): Adding files ":files"', array(':files' => $files)), 'DEBUG/cdn');

            if (!$section) {
                throw new OutOfBoundsException(tr('cdn_add_files(): No section specified'), 'not-specified');
            }

            if (!$files) {
                throw new OutOfBoundsException(tr('cdn_add_files(): No files specified'), 'not-specified');
            }

            /*
             * In what servers are we going to store these files?
             */
            $files = array_force($files);
            $servers = cdn_assign_servers();
            $file_insert = sql_prepare('INSERT IGNORE INTO `cdn_files` (`servers_id`, `section`, `group`, `file`)
                                    VALUES                         (:servers_id , :section , :group , :file )');

            /*
             * Register at what CDN servers the files will be uploaded, and send the
             * files there
             */
            foreach ($servers as $servers_id => $server) {
                foreach ($files as $url => $file) {
                    log_file(tr('cdn_add_files(): Added file ":file" with url ":url" to CDN server ":server"', array(':file' => $file, ':url' => $url, ':server' => $server)), 'DEBUG/cdn');

                    $file_insert->execute(array(':servers_id' => $servers_id,
                        ':section' => $section,
                        ':group' => $group,
                        ':file' => str_starts($url, '/')));
                }

                /*
                 * Send the files
                 */
                cdn_send_files($files, $server, $section, $group);
            }

            /*
             * Now that the file has been sent to the CDN system delete the file
             * locally
             */
            if ($delete) {
                foreach ($files as $url => $file) {
                    file_delete($file, ROOT);
                }
            }

            return count($files);

        } catch (Exception $e) {
            throw new OutOfBoundsException('cdn_add_files(): Failed', $e);
        }
    }



    /*
 * Return a correct URL for CDN objects like css, javascript, image, video, downloadable files and more.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 * @see domain()
 * @see mapped_domain()
 * @version 2.4.9: Added documentation
 *
 * @params string $file
 * @params string $section
 * @params string $default If specified, use this default image if the specified file has not been found
 * @params boolean $force_cdn
 * @return string The result
 */
    function cdn_domain($file = '', $section = 'pub', $default = null, $force_cdn = false)
    {
        global $_CONFIG;
// :TODO: Database CDN servers (for the CDN network) have their own protocol (http or https) specified and thus will ignore the $_CONFIG[session][secure] directive, fix this!!!
        try {
            if (!$_CONFIG['cdn']['enabled'] and !$force_cdn) {
                if ($section == 'pub') {
                    $section = not_empty($_CONFIG['cdn']['prefix'], '/');
                }

                return domain($file, null, $section, $_CONFIG['cdn']['domain'], null, false);
            }

            if ($section == 'pub') {
                /*
                 * Process pub files, "system" files like .css, .js, static website
                 * images ,etc
                 */
                if (!isset($_SESSION['cdn'])) {
                    /*
                     * Get a CDN server for this session
                     */
                    $_SESSION['cdn'] = sql_get('SELECT   `baseurl`

                                            FROM     `cdn_servers`

                                            WHERE    `status` IS NULL

                                            ORDER BY RAND() LIMIT 1', true);

                    if (empty($_SESSION['cdn'])) {
                        /*
                         * There are no CDN servers available!
                         * Switch to working without CDN servers
                         */
                        notify(array('code' => 'invalid',
                            'groups' => 'developers',
                            'title' => tr('Invalid configuration'),
                            'message' => tr('cdn_domain(): The CDN system is enabled but there are no CDN servers configured')));

                        $_CONFIG['cdn']['enabled'] = false;
                        return cdn_domain($file, $section);

                    } else {
                        $_SESSION['cdn'] = slash($_SESSION['cdn']) . 'pub/' . strtolower(str_replace('_', '-', PROJECT) . '/');
                    }
                }

                if (!empty($_CONFIG['cdn']['prefix'])) {
                    $file = $_CONFIG['cdn']['prefix'] . $file;
                }

                return $_SESSION['cdn'] . str_starts_not($file, '/');
            }

            /*
             * Get this URL from the CDN system
             */
            $url = sql_get('SELECT    `cdn_files`.`file`,
                                  `cdn_files`.`servers_id`,

                                  `cdn_servers`.`baseurl`

                        FROM      `cdn_files`

                        LEFT JOIN `cdn_servers`
                        ON        `cdn_files`.`servers_id` = `cdn_servers`.`id`

                        WHERE     `cdn_files`.`file` = :file
                        AND       `cdn_servers`.`status` IS NULL

                        ORDER BY  RAND()

                        LIMIT     1',

                array(':file' => $file));

            if ($url) {
                /*
                 * Yay, found the file in the CDN database!
                 */
                return slash($url['baseurl']) . strtolower(str_replace('_', '-', PROJECT)) . $url['file'];
            }

            /*
             * The specified file is not found in the CDN system, return a default
             * image instead
             */
            if (!$default) {
                $default = $_CONFIG['cdn']['img']['default'];
            }

            return cdn_domain($default, 'pub');

// :TODO: What why where?
            ///*
            // * We have a CDN server in session? If not, get one.
            // */
            //if(isset_get($_SESSION['cdn']) === null) {
            //    $server = sql_get('SELECT `baseurl` FROM `cdn_servers` WHERE `status` IS NULL ORDER BY RAND() LIMIT 1', true);
            //
            //    if(!$server) {
            //        /*
            //         * Err we have no CDN servers, though CDN is configured.. Just
            //         * continue locally?
            //         */
            //        notify('no-cdn-servers', tr('CDN system is enabled, but no availabe CDN servers were found'), 'developers');
            //        $_SESSION['cdn'] = false;
            //        return domain($url, $query, $prefix);
            //    }
            //
            //    $_SESSION['cdn'] = slash($server).strtolower(str_replace('_', '-', PROJECT));
            //}
            //
            //return $_SESSION['cdn'].$url;

        } catch (Exception $e) {
            throw new OutOfBoundsException('cdn_domain(): Failed', $e);
        }
    }



}
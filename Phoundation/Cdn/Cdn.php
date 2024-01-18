<?php

declare(strict_types=1);

namespace Phoundation\Cdn;


use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;


/**
 * Class Cdn
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Cdn
{
    /**
     * Adds the required number of copies of the specified file to random CDN servers
     *
     * @param $files
     * @param $section
     * @param $group
     * @param $delete
     * @return void
     */
    public static function addFiles(string|array $files, string $section = 'pub', $group = null, bool $delete = true): void
    {
        if (!Config::get('web.cdn.enabled', true)) {
            return;
        }

        Log::action(tr('Adding files ":files" to CDN', [':files' => $files]));

        if (!$section) {
            throw new OutOfBoundsException(tr('No section specified'));
        }

        if (!$files) {
            throw new OutOfBoundsException(tr('No files specified'));
        }
// TODO Implement
    }


    /**
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
                        Notification(array('code' => 'invalid',
                            'groups' => 'developers',
                            'title' => tr('Invalid configuration'),
                            'message' => tr('cdn_domain(): The CDN system is enabled but there are no CDN servers configured')));

                        $_CONFIG['cdn']['enabled'] = false;
                        return cdn_domain($file, $section);

                    } else {
                        $_SESSION['cdn'] = Strings::slash($_SESSION['cdn']) . 'pub/' . strtolower(str_replace('_', '-', PROJECT) . '/');
                    }
                }

                if (!empty($_CONFIG['cdn']['prefix'])) {
                    $file = $_CONFIG['cdn']['prefix'] . $file;
                }

                return $_SESSION['cdn'] . Strings::startsNotWith($file, '/');
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
                return Strings::slash($url['baseurl']) . strtolower(str_replace('_', '-', PROJECT)) . $url['file'];
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
            //if (isset_get($_SESSION['cdn']) === null) {
            //    $restrictions = sql_get('SELECT `baseurl` FROM `cdn_servers` WHERE `status` IS NULL ORDER BY RAND() LIMIT 1', true);
            //
            //    if (!$restrictions) {
            //        /*
            //         * Err we have no CDN servers, though CDN is configured.. Just
            //         * continue locally?
            //         */
            //        Notification('no-cdn-servers', tr('CDN system is enabled, but no availabe CDN servers were found'), 'developers');
            //        $_SESSION['cdn'] = false;
            //        return domain($url, $query, $prefix);
            //    }
            //
            //    $_SESSION['cdn'] = Strings::slash($restrictions).strtolower(str_replace('_', '-', PROJECT));
            //}
            //
            //return $_SESSION['cdn'] . $url;

        } catch (Exception $e) {
            throw new OutOfBoundsException('cdn_domain(): Failed', $e);
        }
    }


}
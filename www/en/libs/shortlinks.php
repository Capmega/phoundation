<?php
/*
 * Shortlink library
 *
 * This library contains functions to create shortlinks with multiple providers
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @version 2.0.2: Added library and documentation
 * @package shortlink
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 *
 * @return void
 */
function shortlink_library_init() {
    try {
        load_libs('curl');
        load_config('shortlink');

    }catch(Exception $e) {
        throw new CoreException('shortlink_library_init(): Failed', $e);
    }
}



/*
 * Validate and sanitize a shortlink array
 *
 * This function will validat and sanitize the specified shortlink array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see shortlink_insert()
 * @see shortlink_update()
 * @version 2.0.2: Added function and documentation
 *
 * @param params $link The shortlink array to sanitize
 * @return parans the shortlink array, validated and sanitized
 */
function shortlink_validate($link) {
    try {
        load_libs('validate,seo');

        $v = new ValidateForm($link, 'id,name,url,description');

        /*
         * Validate name
         */
        if ($link['name']) {
            $v->hasMinChars($link['name'],  2, tr('Please ensure the link name has at least 2 characters'));

        } else {
            $link['name'] = '';
        }

        /*
         * Validate description
         */
        if ($link['description']) {
            $v->hasMinChars($link['description'], 16, tr('Please ensure the link description has at least 16 characters'));
            $v->hasMaxChars($link['description'], 2047, tr('Please ensure the link description has less than 2047 characters'));

        } else {
            $link['description'] = '';
        }

        $v->isValid();

        /*
         * Set seoname
         */
        $link['seoname'] = seo_unique($link['name'], 'shortlinks', isset_get($link['id'], 0));

        return $link;

    }catch(Exception $e) {
        throw new CoreException('shortlink_validate(): Failed', $e);
    }
}



/*
 * Create a short URL code
 *
 * This function will create a short URL code, link it to the specified URL and store it in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see shortlink_update()
 * @see shortlink_validate()
 * @version 2.0.2: Added function and documentation
 *
 * @param params $link The shortlink array to insert
 * @return params The inserted link array with id added
 */
function shortlink_create($link) {
    try {
        $link = shortlink_validate($link);

        /*
         * Assign new random and guaranteed unique code
         */
        $link['code'] = shortlink_get_code();

        sql_query('INSERT INTO `shortlinks` (`createdby`, `meta_id`, `name`, `seoname`, `code`, `url`, `description`)
                   VALUES                   (:createdby , :meta_id , :name , :seoname , :code , :url ,  description )',

                   array(':createdby'   => isset_get($_SESSION['user']['id']),
                         ':meta_id'     => meta_action(),
                         ':name'        => $link['name'],
                         ':seoname'     => $link['seoname'],
                         ':code'        => $link['code'],
                         ':url'         => $link['url'],
                         ':description' => $link['description']));

        $link['id'] = sql_insert_id();

        return $link;

    }catch(Exception $e) {
        throw new CoreException('shortlink_create(): Failed', $e);
    }
}



/*
 * Update the specified shortlink array
 *
 * This function will update the specified shortlink array.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see shortlink_create()
 * @see shortlink_validate()
 * @see shortlink_get_code()
 * @version 2.0.2: Added function and documentation
 *
 * @param params $link The shortlink array to update
 * @return params The updated link array
 */
function shortlink_update($link) {
    try {
        $link = shortlink_validate($link);

        sql_query('UPDATE `shortlinks`

                   SET    `name`        = :name,
                          `seoname`     = :seoname,
                          `url`         = :url,
                          `description` = :description

                   WHERE  `id`          = :id',

                   array(':id'          => $link['id'],
                         ':name'        => $link['name'],
                         ':seoname'     => $link['seoname'],
                         ':url'         => $link['url'],
                         ':description' => $link['description']));

        return $link;

    }catch(Exception $e) {
        throw new CoreException('shortlink_update(): Failed', $e);
    }
}



/*
 * Generates and returns a guaranteed unique code
 *
 * This function will update the specified shortlink array.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see shortlink_create()
 * @version 2.0.2: Added function and documentation
 *
 * @return string a unique code
 */
function shortlink_get_code() {
    try {
        while (++$attempts < 32) {
            $code   = str_random(8);
            $exists = sql_get('SELECT `id` FROM `shortlinks` WHERE `code` = :code', true, array(':code' => $code));

            if ($exists) {
                log_file(tr('Generated random code ":code" already exists for entry ":entry", trying a new code', array(':code' => $code, ':entry' => $exists)), 'shortlink', 'yellow');
                continue;
            }

            /*
             * Doesn't exist yet, yay!
             */
            return $code;
        }

        throw new CoreException(tr('shortlink_get_code(): Failed to find a unique URL code after ":tries" tries', array(':tries' => $attempts)), 'failed');

    }catch(Exception $e) {
        throw new CoreException('shortlink_get_code(): Failed', $e);
    }
}



/*
 * Redirect to the URL linked to the specified short code
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see shortlink_create()
 * @note This function will cause a redirect  header be sent to the client, and then kills the current process
 * @version 2.0.2: Added function and documentation
 * @example
 * code
 * shortlink_redirect('FOOBAR');
 * /code
 *
 * This will send a Location: header to the client if FOOBAR exists. If foobar does not exist, it will redirec to the main shortlink management page
 *
 * @param params $params A parameters array
 * @return void
 */
function shortlink_redirect($code) {
    try {
        $url = sql_get('SELECT `url` FROM `shortlinks` WHERE `code` = :code', true, array('code' => $code));

        if (!$url) {
            /*
             * Specified shortlink code does not exist
             */
            throw new CoreException(tr('shortlink(): The specified URL code ":code" does not exist'), 404);
        }

        log_file(tr('Redirecting IP ":ip" to URL ":url" for code ":code"', array(':ip' => $_SERVER['REMOTE_ADDR'], ':url' => $url, ':code' => $code)), 'shortlink', 'cyan');
        redirect($url);

    }catch(Exception $e) {
        throw new CoreException('shortlink_redirect(): Failed', $e);
    }
}



/*
 * Validates if the specified provider exists and returns it. If no provider was specified, the default provider will be returned
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see shortlink_create()
 * @see shortlink_create() Used to convert the sitemap entry dates
 * @version 1.22.0: Added function
 *
 * @param string $provider The provider that should be validate, or no provider (in which case the default )
 * @return Either the specified provider, or if no provider was specified, the default provider
 */
function shortlink_get_provider($provider = null) {
    global $_CONFIG;

    try {
        switch ($provider) {
            case '':
                /*
                 * No provider specified, use the default provider, and validate
                 * that it exists (Hey, somebody can make a typo!)
                 */
                return shortlink_get_provider($_CONFIG['shortlink']['default']);

            case 'bitly':
                // no-break
            case 'internal':
                /*
                 * These are supported providers
                 */
                break;

            default:
                throw new CoreException(tr('shortlink_get_provider(): Unknown provider ":provider" specified', array(':provider' => $provider)), 'unknown');
        }

        return $provider;

    }catch(Exception $e) {
        throw new CoreException('shortlink_get_provider(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see shortlink_create()
 * @see shortlink_create() Used to convert the sitemap entry dates
 * @version 1.22.0: Added function
 *
 * @return
 */
function shortlink_get_access_token($provider = null) {
    global $_CONFIG;

    try {
        $provider = shortlink_get_provider($provider);

        switch ($provider) {
            case 'capmega':
                $results = curl_get(array('url'      => 'https://api.capmega.com/oauth/access_token',
                                          'user_pwd' => $_CONFIG['shortlink']['capmega']['account']));

            case 'bitly':
                $results = curl_get(array('url'      => 'https://api-ssl.bitly.com/oauth/access_token',
                                          'user_pwd' => $_CONFIG['shortlink']['bitly']['account'],
                                          'method'   => 'post'));
        }

        return $results;

    }catch(Exception $e) {
        throw new CoreException('shortlink_get_access_token(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see empty_install()
 * @see shortlink_create() Used to convert the sitemap entry dates
 * @version 1.22.0: Added function
 *
 * @param string $url
 * @param string $provider
 * @return string a shortlink URL from the specified provider for the specified URL
 */
function shortlink_create($url, $provider = null) {
    try {
        $token = shortlink_get_access_token($provider);

        switch ($provider) {
            case 'capmega':
under_construction();

            case 'bitly':
                $result = curl_get(array('url'     => 'https://api-ssl.bitly.com/v4/bitlinks?access_token='.$token,

                                         'post'    => json_encode(array('long_url' => $url)),

                                         'headers' => array('Authorization: Bearer {$token}',
                                                            'Content-Type: application/json',
                                                            'Content-Length: '.strlen($json_string))));

                $result = json_decode_custom($result);

                if (empty($result['link'])) {
                    throw new CoreException(tr('shortlink_create(): Invalid response received from provider "bitly" for the specified URL ":url"', array(':url' => $url)), 'invalid');
                }

                return $result['link'];
        }

    }catch(Exception $e) {
        throw new CoreException('shortlink_create(): Failed', $e);
    }
}

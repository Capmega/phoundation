<?php

namespace Phoundation\Core;

/**
 * Class Locale
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Locale
{
///*
// *
// */
//function language_lock($language, $script = null)
//{
//    global $core;
//
//    static $checked = false;
//    static $incorrect = false;
//
//    try {
//        if (is_array($script)) {
//            /*
//             * Script here will contain actually a list of all scripts for
//             * each language. This can then be used to determine the name
//             * of the script in the correct language to build linksx
//             */
//            $core->register['scripts'] = $script;
//        }
//
//        /*
//         *
//         */
//        if (!$checked) {
//            $checked = true;
//
//            if ($language and (LANGUAGE !== $language)) {
//                $incorrect = true;
//            }
//        }
//
//        if (!is_array($script)) {
//            /*
//             * Show the specified script, it will create the content for
//             * this $core->register['script']
//             */
//            page_show($script);
//        }
//
//        /*
//         * Script and language match, continue
//         */
//        if ($incorrect) {
//            page_show(404);
//        }
//
//    } catch (Exception $e) {
//        throw new OutOfBoundsException(tr('language_lock(): Failed'), $e);
//    }
//}



    /*
     * Set PHP locale data from specified configuration. If no configuration was specified, use $_CONFIG[locale] instead
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @version 2.8.15: Added function and documentation
     * @version 2.8.24: Now returns LC_ALL locale
     * @note If LC_ALL is specified, it will be applied first to set the default value for all other parameters. Then all the other parameters will be applied, if specified
     * @note Each LC_* value can contain a :LANGUAGE and :COUNTRY tag. These tags will be replaced with an ISO_639-1 language code / ISO 3166-1 alpha-2 country code respectitively
     * @note If LANGUAGE is not available, the default language in $_CONFIG[language][default] will be used
     * @note If the current country is not available, the default country code US will be assumed
     *
     * @param params $params A parameters array
     * @param optional $string LC_ALL      Default value for all locale parameters
     * @param optional $string LC_COLLATE  Default value for LC_COLLATE parameter
     * @param optional $string LC_CTYPE    Default value for LC_CTYPE parameter
     * @param optional $string LC_MONETARY Default value for LC_MONETARY parameter
     * @param optional $string LC_NUMERIC  Default value for LC_NUMERIC parameter
     * @param optional $string LC_TIME     Default value for all LC_TIME parameter
     * @param optional $string LC_MESSAGES Default value for LC_MESSAGES parameter
     * @return string the LC_ALL locale
     */
    function set($data = null)
    {
        global $_CONFIG;

        try {
            $retval = '';

            if (!$data) {
                $data = $_CONFIG['locale'];
            }

            if (!is_array($data)) {
                throw new OutOfBoundsException(tr('set_locale(): Specified $data should be an array but is an ":type"', array(':type' => gettype($data))), 'invalid');
            }

            /*
             * Determine language and location
             */
            if (defined('LANGUAGE')) {
                $language = LANGUAGE;

            } else {
                $language = $_CONFIG['language']['default'];
            }

            if (isset($_SESSION['location']['country']['code'])) {
                $country = strtoupper($_SESSION['location']['country']['code']);

            } else {
                $country = $_CONFIG['location']['default_country'];
            }

            /*
             * First set LC_ALL as a baseline, then each individual entry
             */
            if (isset($data[LC_ALL])) {
                $data[LC_ALL] = str_replace(':LANGUAGE', $language, $data[LC_ALL]);
                $data[LC_ALL] = str_replace(':COUNTRY', $country, $data[LC_ALL]);

                setlocale(LC_ALL, $data[LC_ALL]);
                $retval = $data[LC_ALL];
                unset($data[LC_ALL]);
            }

            /*
             * Apply all parameters
             */
            foreach ($data as $key => $value) {
                if ($key === 'country') {
                    /*
                     * Ignore this key
                     */
                    continue;
                }

                if ($value) {
                    /*
                     * Ignore this empty value
                     */
                    continue;
                }

                $value = str_replace(':LANGUAGE', $language, $value);
                $value = str_replace(':COUNTRY', $country, $value);

                setlocale($key, $value);
            }

            return $retval;

        } catch (Exception $e) {
            throw new OutOfBoundsException(tr('set_locale(): Failed'), $e);
        }
    }
}
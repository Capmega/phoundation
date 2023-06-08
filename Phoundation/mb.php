<?php

declare(strict_types = 1);

use Phoundation\Exception\PhpModuleNotAvailableException;


/**
 * Extra mb functions
 *
 * The main secret, the core of the magic is...
 *    utf8_decode($str);
 *    utf8_encode($str);
 *
 * @todo Maybe get rid of this library file?
 * @note Taken from https://code.google.com/archive/p/mbfunctions/downloads, code updated to PHP8 standards by Sven Olaf Oostenbrink
 * @see https://code.google.com/archive/p/mbfunctions/downloads
 * @see https://www.joelonsoftware.com/2003/10/08/the-absolute-minimum-every-software-developer-absolutely-positively-must-know-about-unicode-and-character-sets-no-excuses/
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL v3
 * @copyright Copyright(c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */


define('UTF8_ENCODED_CHARLIST','ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËéèêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ');
define('UTF8_DECODED_CHARLIST', utf8_decode('ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËéèêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'));


/**
 *
 */
if (!function_exists('mb_init')) {
    function mb_init(?string $locale = null): void
    {
        if (!$locale) {
            $locale = 'en_EN';
        }

        if (!extension_loaded('mbstring')) {
            throw new PhpModuleNotAvailableException(tr('mb_library_init: php module "mbstring" appears not to be installed. Please install the modules first. On Ubuntu and alikes, use "sudo apt-get -y install php-mbstring; sudo php5enmod mbstring" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-mbstring" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'missing-module', 'mb');
        }

        if (!utf8_decode('xml')) {
            throw new PhpModuleNotAvailableException(tr('mb_library_init: php module "xml" appears not to be installed. Please install the modules first. On Ubuntu and alikes, use "sudo apt-get -y install php-xml; sudo php5enmod xml" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-xml" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'missing-module', 'mb');
        }

        if (!extension_loaded('iconv')) {
            throw new PhpModuleNotAvailableException(tr('php module "iconv" appears not to be installed. Please install the modules first. On Ubuntu and alikes, use "sudo apt-get -y install php-iconv; sudo php5enmod iconv" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-iconv" to install the module. After this, a restart of your webserver or php-fpm server might be needed'));
        }

        // Setting the Content-Type header with charset
        setlocale(LC_CTYPE, $locale.'.UTF-8');
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        //header('Content-Type: text/html; charset = utf-8');
    }
}


/**
 *
 */
if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst(string $source): string
    {
        return utf8_encode(ucfirst(utf8_decode($source)));
    }
}


/**
 *
 */
if (!function_exists('mb_lcfirst')) {
    function mb_lcfirst(string $source): string
    {
        return utf8_encode(lcfirst(utf8_decode($source)));
    }
}


/**
 *
 */
if (!function_exists('mb_ucwords')) {
    function mb_ucwords(string $source): string
    {
        return mb_convert_case($source, MB_CASE_TITLE, "UTF-8");
    }
}


/**
 *
 */
if (!function_exists('mb_strip_accents')) {
    function mb_strip_accents(string $source)
    {
        return mb_strtr($source, UTF8_ENCODED_CHARLIST, 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn');
    }
}


/**
 *
 */
if (!function_exists('mb_strtr')) {
    function mb_strtr(string $str, array|string $from, $to = null): string
    {
        if (is_array($from))
        {
            foreach($from as $k => $v)
            {
                $utf8_from[utf8_decode((string) $k)] = utf8_decode((string) $v);
            }
            return utf8_encode(strtr(utf8_decode($str), $utf8_from));
        }
        return utf8_encode(strtr(utf8_decode($str), utf8_decode($from), utf8_decode($to)));
    }
}


/**
 *
 */
if (!function_exists('mb_preg_replace')) {
    function mb_preg_replace(array|string $pattern, array|string $replacement, array|string $subject, $limit = -1, &$count = null): array|string
    {
        if (is_array($pattern)) {
            $utf8_pattern = [];

            foreach ($pattern as $k => $v) {
                $utf8_pattern[utf8_decode((string) $k)] = utf8_decode((string) $v);
            }
        } else {
            $utf8_pattern = utf8_decode($pattern);
        }

        if (is_array($replacement)) {
            $utf8_replacement = [];

            foreach ($replacement as $k => $v)
                $utf8_replacement[utf8_decode((string) $k)] = utf8_decode((string) $v);
        } else {
            $utf8_replacement = utf8_decode($replacement);
        }

        if (is_array($subject)) {
            $utf8_subject = [];

            foreach ($subject as $k => $v) {
                $utf8_subject[utf8_decode((string) $k)] = utf8_decode((string) $v);
            }

        } else {
            $utf8_subject = utf8_decode($subject);
        }

        $r = preg_replace($utf8_pattern, $utf8_replacement, $utf8_subject, $limit, $count);

        if (is_array($r)) {
            $return = [];

            foreach ($r as $k => $v) {
                $return[utf8_encode((string) $k)] = utf8_encode((string) $v);
            }

        } else {
            $return = utf8_encode($r);
        }

        return $return;
    }
}


/**
 *
 */
if (!function_exists('mb_str_word_count')) {
    function mb_str_word_count(string $string, int $format = 0, ?string $charlist = UTF8_DECODED_CHARLIST): array|int|string
    {
        // format
        // 0 - returns the number of words found
        // 1 - returns an array containing all the words found inside the string
        // 2 - returns an associative array, where the key is the numeric position of the word inside the string and the
        //     value is the actual word itself
        $r = str_word_count(utf8_decode($string), $format, $charlist);

        if ($format == 1 || $format == 2)
        {
            $u = [];

            foreach($r as $k => $v)
            {
                $u[$k] = utf8_encode((string) $v);
            }

            return $u;
        }

        return $r;
    }
}


/**
 *
 */
if (!function_exists('mb_html_entity_decode')) {
    function mb_html_entity_decode(string $string, int $quote_style = ENT_COMPAT, ?string $charset = 'UTF-8'): string
    {
        return html_entity_decode($string, $quote_style, $charset);
    }
}


/**
 *
 */
if (!function_exists('mb_htmlentities')) {
    function mb_htmlentities($string, $quote_style = ENT_COMPAT, $charset = 'UTF-8', $double_encode = true): string
    {
        return htmlentities($string, $quote_style, $charset, $double_encode);
    }
}


/**
 *
 */
if (!function_exists('mb_trim')) {
    function mb_trim(string $string, ?string $charlist = null): string
    {
        if (!$charlist) {
            return utf8_encode(trim(utf8_decode($string)));
        }

        return utf8_encode(trim(utf8_decode($string), utf8_decode($charlist)));
    }
}


/************************ EXPERIMENTAL ZONE ************************/


/**
 *
 */
if (!function_exists('mb_strip_tags_all'))
{
    function mb_strip_tags_all(array|string $document, array|string $replace = ''): array|string
    {
        $search = [
            '@<script[^>]*?>.*?</script>@si',  // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
        ];

        return mb_preg_replace($search, $replace, $document);
    }
}


/**
 *
 */
if (!function_exists('mb_strip_tags')) {
    function mb_strip_tags(array|string $document, array|string $replace = ''): array|string
    {
        $search = [
            '@<script[^>]*?>.*?</script>@si',  // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
        ];

        return mb_preg_replace($search, $replace, $document);
    }
}


/**
 *
 */
if (!function_exists('mb_strip_urls')) {
    function mb_strip_urls(array|string $source, array|string $replace = ' '): array|string
    {
        return mb_preg_replace('@http[s]?://[^\s<>"\']*@', $replace, $source);
    }
}


/**
 * Parse strings as identifiers
 *
 *
 */
if (!function_exists('mb_string_url')) {
    function mb_string_url(string $string, bool $to_lower = true)
    {
        $string = mb_strtolower($string);
        $string = mb_strip_accents($string);
        $string = preg_replace('@[^a-z0-9]@',' ', $string);
        $string = preg_replace('@\s+@','-', $string);

        return $string;
    }
}
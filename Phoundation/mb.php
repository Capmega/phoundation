<?php

declare(strict_types=1);

use Phoundation\Exception\PhpModuleNotAvailableException;


/**
 * Extra mb functions
 *
 * The main secret, the core of the magic is...
 *    mb_convert_encoding((string) $string, 'UTF-8', 'ISO-8859-1');
 *    mb_convert_encoding((string) $string, 'ISO-8859-1', 'UTF-8');
 *
 * @todo      Maybe get rid of this library file?
 * @note      Taken from https://code.google.com/archive/p/mbfunctions/downloads, code updated to PHP8 standards by
 *            Sven Olaf Oostenbrink
 * @see       https://code.google.com/archive/p/mbfunctions/downloads
 * @see       https://www.joelonsoftware.com/2003/10/08/the-absolute-minimum-every-software-developer-absolutely-positively-must-know-about-unicode-and-character-sets-no-excuses/
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://www.gnu.org/licenses/gpl.html GNU GPL v3
 * @copyright Copyright(c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


define('UTF8_ENCODED_CHARLIST', 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËéèêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ');
define('UTF8_DECODED_CHARLIST', mb_convert_encoding('ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËéèêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ', 'UTF-8', 'ISO-8859-1'));


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
            throw new PhpModuleNotAvailableException(tr('php module "mbstring" appears not to be installed. Please install the modules first. On Ubuntu and alikes, use "sudo apt-get -y install php-mbstring; sudo php5enmod mbstring" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-mbstring" to install the module. After this, a restart of your webserver or php-fpm server might be needed'));
        }

        if (!extension_loaded('xml')) {
            throw new PhpModuleNotAvailableException(tr('php module "xml" appears not to be installed. Please install the modules first. On Ubuntu and alikes, use "sudo apt-get -y install php-xml; sudo php5enmod xml" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-xml" to install the module. After this, a restart of your webserver or php-fpm server might be needed'));
        }

        if (!extension_loaded('iconv')) {
            throw new PhpModuleNotAvailableException(tr('php module "iconv" appears not to be installed. Please install the modules first. On Ubuntu and alikes, use "sudo apt-get -y install php-iconv; sudo php5enmod iconv" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-iconv" to install the module. After this, a restart of your webserver or php-fpm server might be needed'));
        }

        // Setting the Content-Type header with charset
        setlocale(LC_CTYPE, $locale . '.UTF-8');
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
        return mb_convert_encoding(ucfirst(mb_convert_encoding($source, 'UTF-8', 'ISO-8859-1')), 'ISO-8859-1', 'UTF-8');
    }
}


/**
 *
 */
if (!function_exists('mb_lcfirst')) {
    function mb_lcfirst(string $source): string
    {
        return mb_convert_encoding(lcfirst(mb_convert_encoding($source, 'UTF-8', 'ISO-8859-1')), 'ISO-8859-1', 'UTF-8');
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
    function mb_strtr(string $source, array|string $from, $to = null): string
    {
        if (is_array($from)) {
            foreach ($from as $key => $value) {
                $utf8_from[mb_convert_encoding((string)$key, 'UTF-8', 'ISO-8859-1')] = mb_convert_encoding((string)$value, 'UTF-8', 'ISO-8859-1');
            }
            return mb_convert_encoding(strtr(mb_convert_encoding($source, 'UTF-8', 'ISO-8859-1'), $utf8_from), 'ISO-8859-1', 'UTF-8');
        }
        return mb_convert_encoding(strtr(mb_convert_encoding($source, 'UTF-8', 'ISO-8859-1'), mb_convert_encoding($from, 'UTF-8', 'ISO-8859-1'), mb_convert_encoding($to, 'UTF-8', 'ISO-8859-1')), 'ISO-8859-1', 'UTF-8');
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

            foreach ($pattern as $key => $value) {
                $utf8_pattern[mb_convert_encoding((string)$key, 'UTF-8', 'ISO-8859-1')] = mb_convert_encoding((string)$value, 'UTF-8', 'ISO-8859-1');
            }
        } else {
            $utf8_pattern = mb_convert_encoding($pattern, 'UTF-8', 'ISO-8859-1');
        }

        if (is_array($replacement)) {
            $utf8_replacement = [];

            foreach ($replacement as $key => $value) {
                $utf8_replacement[mb_convert_encoding((string)$key, 'UTF-8', 'ISO-8859-1')] = mb_convert_encoding((string)$value, 'UTF-8', 'ISO-8859-1');
            }
        } else {
            $utf8_replacement = mb_convert_encoding($replacement, 'UTF-8', 'ISO-8859-1');
        }

        if (is_array($subject)) {
            $utf8_subject = [];

            foreach ($subject as $key => $value) {
                $utf8_subject[mb_convert_encoding((string)$key, 'UTF-8', 'ISO-8859-1')] = mb_convert_encoding((string)$value, 'UTF-8', 'ISO-8859-1');
            }

        } else {
            $utf8_subject = mb_convert_encoding((string)$subject, 'UTF-8', 'ISO-8859-1');
        }

        $r = preg_replace($utf8_pattern, $utf8_replacement, $utf8_subject, $limit, $count);

        if (is_array($r)) {
            $return = [];

            foreach ($r as $key => $value) {
                $return[mb_convert_encoding((string)$key, 'ISO-8859-1', 'UTF-8')] = mb_convert_encoding((string)$value, 'ISO-8859-1', 'UTF-8');
            }

        } else {
            $return = mb_convert_encoding($r, 'ISO-8859-1', 'UTF-8');
        }

        return $return;
    }
}


/**
 *
 */
if (!function_exists('mb_str_word_count')) {
    function mb_str_word_count(string $string, int $format = 0, ?string $charlist = UTF8_DECODED_CHARLIST): array|string|int
    {
        // format
        // 0 - returns the number of words found
        // 1 - returns an array containing all the words found inside the string
        // 2 - returns an associative array, where the key is the numeric position of the word inside the string and the
        //     value is the actual word itself
        $r = str_word_count(mb_convert_encoding((string)$string, 'UTF-8', 'ISO-8859-1'), $format, $charlist);

        if ($format == 1 || $format == 2) {
            $u = [];

            foreach ($r as $k => $v) {
                $u[$k] = mb_convert_encoding((string)$v, 'ISO-8859-1', 'UTF-8');
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
            return mb_convert_encoding(trim(mb_convert_encoding((string)$string, 'UTF-8', 'ISO-8859-1')), 'ISO-8859-1', 'UTF-8');
        }

        return mb_convert_encoding(trim(mb_convert_encoding((string)$string, 'UTF-8', 'ISO-8859-1'), mb_convert_encoding((string)$charlist, 'UTF-8', 'ISO-8859-1')), 'ISO-8859-1', 'UTF-8');
    }
}


/************************ EXPERIMENTAL ZONE ************************/


/**
 *
 */
if (!function_exists('mb_strip_tags_all')) {
    function mb_strip_tags_all(array|string $document, array|string $replace = ''): array|string
    {
        $search = [
            '@<script[^>]*?>.*?</script>@si',
            // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',
            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',
            // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@',
            // Strip multi-line comments including CDATA
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
            '@<script[^>]*?>.*?</script>@si',
            // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',
            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',
            // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@',
            // Strip multi-line comments including CDATA
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
        $string = preg_replace('@[^a-z0-9]@', ' ', $string);
        $string = preg_replace('@\s+@', '-', $string);

        return $string;
    }
}

<?php
/**
 * functions file functions.php
 *
 * This is the core functions library file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright 2021 Sven Olaf Oostenbrink <sven@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 */

use Phoundation\Core\CoreException;
use Phoundation\Core\Json\Arrays;
use Phoundation\Core\Json\Strings;


/**
 * tr() is a translator marker function. It basic function is to tell the
 * translation system that the text within should be translated.
 *
 * Since text may contain data from either variables or function output, and
 * translators should not be burdened with copying variables or function calls,
 * all variable data should be identified in the text by a :marker, and the
 * :marker should be a key (with its value) in the $replace array.
 *
 * $replace values are always processed first by Strings::log() to ensure they are
 * readable texts, so the texts sent to tr() do NOT require Strings::log().
 *
 * On non production systems, tr() will perform a check on both the $text and
 * $replace data to ensure that all markers have been replaced, and non were
 * forgotten. If results were found, an exception will be thrown. This
 * behaviour does NOT apply to production systems
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2021 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package system
 *
 * @param string $text
 * @param array|null $replace
 * @param boolean $verify
 * @return string
 */
function tr(string $text, ?array $replace = null, bool $verify = true): string
{
    global $_CONFIG;

    try{
        if($replace) {
            foreach($replace as &$value) {
                $value = Strings::log($value);
            }

            unset($value);

            $text = str_replace(Arrays::keys($replace), Arrays::values($replace), $text, $count);

            /*
             * Only on non production machines, crash when not all entries were replaced as an extra check.
             */
            if(empty($_CONFIG['production']) and $verify) {
                if($count != count($replace)) {
                    foreach($replace as $value) {
                        if(strstr($value, ':')) {
                            /*
                             * The one of the $replace values contains :blah
                             * This will cause the detector to fire off
                             * incorrectly. Ignore this.
                             */
                            return $text;
                        }
                    }

                    throw new CoreException('tr(): Not all specified keywords were found in text');
                }

                /*
                 * Do NOT check for :value here since the given text itself may contain :value (ie, in prepared statements!)
                 */
            }

            return $text;
        }

        return $text;

    } catch (Exception $e) {
        /*
         * Do NOT use tr() here for obvious endless loop reasons!
         */
        throw new CoreException('tr(): Failed with text "'.Strings::log($text).'". Very likely issue with $replace not containing all keywords, or one of the $replace values is non-scalar', $e);
    }
}




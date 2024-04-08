<?php

declare(strict_types=1);

namespace Phoundation\Seo;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;

/**
 * Class Seo
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */
class Seo
{
    /**
     * Generate a unique seo name
     *
     * This function will use Seo::string() to convert the specified $source variable to a seo optimized string, and
     * then it will check the specified $table to ensure that it does not yet exist. If the current seo string already
     * exists, it will be expanded with a natural number and the table will be checked again. If the seo string is still
     * found, this number will be incremented each loop, until the string is no longer found
     *
     * @param array|string   $source
     * @param string         $table
     * @param array|int|null $ownid
     * @param string         $column
     * @param string         $replace
     * @param null           $first_suffix
     * @param string|null    $connector_name If specified, use the specified database connector instead of the default
     *                                       database connector
     *
     * @return string|null                  The specified $source string seo optimized, which does not yet exist in the
     *                                      specified $table
     * @see Seo::string()
     * @example
     * code
     * $name   = 'Capmega';
     * $result = seo_unique($name, 'customers', 15);
     * showdie($result);
     * /code
     *
     * This would return
     * code
     * capmega
     * /code
     *
     */
    // :TODO: Update to use bound variable queries
    // :TODO: Add WAY more comments in code, I can barely figure out whats going on there
    public static function unique(array|string $source, string $table, array|int|null $ownid = null, string $column = 'seoname', string $replace = '-', $first_suffix = null): string|null
    {
        // Prepare string
        $id = 0;
        if (empty($source)) {
            // If the given string is empty, then treat seoname as null, this should not cause indexing issues
            return null;
        }
        if (is_array($source)) {
            /*
             * The specified source is a key => value array which can be used
             * for unique entries spanning multiple columns
             *
             * Example: geo_cities has unique states_id with seoname
             * $source = array('seoname'   => 'cityname',
             *                 'states_id' => 3);
             *
             * NOTE: The first column will have the identifier added
             */
            foreach ($source as $column => &$value) {
                if (empty($first)) {
                    $first = [$column => $value];
                }
                $value = trim(static::string($value, $replace));
            }
            unset($value);

        } else {
            $source = trim(static::string($source, $replace));
        }
        // Filter out the id of the record itself
        if ($ownid) {
            if (is_scalar($ownid)) {
                $ownid = ' AND `id` != ' . $ownid;

            } elseif (is_array($ownid)) {
                $key = key($ownid);
                if (!is_numeric($ownid[$key])) {
                    if (!is_scalar($ownid[$key])) {
                        throw new OutOfBoundsException(tr('Invalid $ownid array value datatype specified, should be scalar and numeric, but is ":type"', [
                            ':type' => gettype($ownid[$key]),
                        ]));
                    }
                    $ownid[$key] = '"' . $ownid[$key] . '"';
                }
                $ownid = ' AND `' . $key . '` != ' . $ownid[$key];

            } else {
                throw new OutOfBoundsException(tr('Invalid $ownid datatype specified, should be either scalar, or array, but is ":type"', [
                    ':type' => gettype($ownid),
                ]));
            }

        } else {
            $ownid = '';
        }
        // If the seostring exists, add an identifier to it.
        while (true) {
            if (is_array($source)) {
                // Check on multiple columns, add identifier on first column value
                if ($id) {
                    if ($first_suffix) {
                        $source[key($first)] = reset($first) . trim(static::string($first_suffix, $replace));
                        $first_suffix        = null;
                        $id--;

                    } else {
                        $source[key($first)] = reset($first) . $id;
                    }
                }
                $exists = sql()->get('SELECT COUNT(*) AS `count` FROM `' . $table . '` WHERE `' . Arrays::implodeWithKeys($source, '" AND `', '` = "', true) . '"' . $ownid . ';');
                if (!$exists) {
                    return $source[key($first)];
                }

            } else {
                if (!$id) {
                    $return = $source;

                } else {
                    if ($first_suffix) {
                        $source       = $source . trim(static::string($first_suffix, $replace));
                        $first_suffix = null;
                        $id--;

                    } else {
                        $return = $source . $id;
                    }
                }
                $exists = sql()->get('SELECT `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` = "' . $return . '"' . $ownid . ';');
                if (!$exists) {
                    return $return;
                }
            }
            $id++;
        }
    }


    /**
     * Return a seo appropriate string for given source string
     */
    public static function string($source, $replace = '-')
    {
        if (Strings::isUtf8($source)) {
            //clean up string
            $source = mb_strtolower(trim(mb_strip_tags($source)));
            //convert spanish crap to english
            $source2 = Strings::convertAccents($source);
            //remove special chars
            $from    = [
                "'",
                '"',
                '\\',
            ];
            $to      = [
                '',
                '',
                '',
            ];
            $source3 = str_replace($from, $to, $source2);
            //remove double spaces
            $source = preg_replace('/\s\s+/', ' ', $source3);
            //Replace anything that is junk
            $last = preg_replace('/[^a-zA-Z0-9]/u', $replace, $source);
            //Remove double "replace" chars
            $last = preg_replace('/\\' . $replace . '\\' . $replace . '+/', '-', $last);

            return trim($last, '-');

        } else {
            //clean up string
            $source = strtolower(trim(strip_tags($source)));
            //convert spanish crap to english
            $source2 = Strings::convertAccents($source);
            //remove special chars
            $from    = [
                "'",
                '"',
                '\\',
            ];
            $to      = [
                '',
                '',
                '',
            ];
            $source3 = str_replace($from, $to, $source2);
            //remove double spaces
            $source = preg_replace('/\s\s+/', ' ', $source3);
            //Replace anything that is junk
            $last = preg_replace('/[^a-zA-Z0-9]/', $replace, $source);
            //Remove double "replace" chars
            $last = preg_replace('/\\' . $replace . '\\' . $replace . '+/', '-', $last);

            return trim($last, '-');
        }
    }
}

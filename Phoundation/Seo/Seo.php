<?php

namespace Phoundation\Seo;

use Exception;
use Phoundation\Databases\Sql;

/**
 * Class Seo
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Core
 */
class Seo
{
    /**
     * Generate an unique seo name
     *
     * This function will use seo_string() to convert the specified $source variable to a seo optimized string, and then it will check the specified $table to ensure that it does not yet exist. If the current seo string already exists, it will be expanded with a natural number and the table will be checked again. If the seo string is still found, this number will be incremented each loop, until the string is no longer found
     *
     * @param scalar $source
     * @param string $table
     * @param null natural $ownid
     * @param string $column
     * @param string $replace
     * @param null $first_suffix
     * @param null string $connector_name If specified, use the specified database connector instead of the default "core" database connector
     * @return string The specified $source string seo optimized, which does not yet exist in the specified $table
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package seo
     * @see seo_string()
     * @version 1.27.0: Added documentation
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
    public function unique(string $source, string $table, ?int $ownid = null, $column = 'seoname', $replace = '-', $first_suffix = null, $connector_name = null): string|null
    {
        /*
         * Prepare string
         */
        $id = 0;

        if (empty($source)) {
            /*
             * If the given string is empty, then treat seoname as null, this should not cause indexing issues
             */
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
                    $first = array($column => $value);
                }

                $value = trim(Seo::string($value, $replace));
            }

            unset($value);

        } else {
            $source = trim(seo_string($source, $replace));
        }

        /*
         * Filter out the id of the record itself
         */
        if ($ownid) {
            if (is_scalar($ownid)) {
                $ownid = ' AND `id` != ' . $ownid;

            } elseif (is_array($ownid)) {
                $key = key($ownid);

                if (!is_numeric($ownid[$key])) {
                    if (!is_scalar($ownid[$key])) {
                        throw new OutOfBoundsException(tr('seo_unique(): Invalid $ownid array value datatype specified, should be scalar and numeric, but is "%type%"', array('%type%' => gettype($ownid[$key]))), 'invalid');
                    }

                    $ownid[$key] = '"' . $ownid[$key] . '"';
                }

                $ownid = ' AND `' . $key . '` != ' . $ownid[$key];

            } else {
                throw new OutOfBoundsException(tr('seo_unique(): Invalid $ownid datatype specified, should be either scalar, or array, but is "%type%"', array('%type%' => gettype($ownid))), 'invalid');
            }

        } else {
            $ownid = '';
        }

        /*
         * If the seostring exists, add an identifier to it.
         */
        while (true) {
            if (is_array($source)) {
                /*
                 * Check on multiple columns, add identifier on first column value
                 */
                if ($id) {
                    if ($first_suffix) {
                        $source[key($first)] = reset($first) . trim(seo_string($first_suffix, $replace));
                        $first_suffix = null;
                        $id--;

                    } else {
                        $source[key($first)] = reset($first) . $id;
                    }
                }

                $exists = SQL::get('SELECT COUNT(*) AS `count` FROM `' . $table . '` WHERE `' . array_implode_with_keys($source, '" AND `', '` = "', true) . '"' . $ownid . ';', true, null, $connector_name);

                if (!$exists) {
                    return $source[key($first)];
                }

            } else {
                if (!$id) {
                    $str = $source;

                } else {
                    if ($first_suffix) {
                        $source = $source . trim(seo_string($first_suffix, $replace));
                        $first_suffix = null;
                        $id--;

                    } else {
                        $str = $source . $id;
                    }
                }

                $exists = SQL::get('SELECT COUNT(*) AS `count` FROM `' . $table . '` WHERE `' . $column . '` = "' . $str . '"' . $ownid . ';', true, null, $connector_name);

                if (!$exists) {
                    return $str;
                }
            }

            $id++;
        }
    }



    /**
     * Return a seo appropriate string for given source string
     */
    function string($source, $replace = '-')
    {
        if (str_is_utf8($source)) {
            load_libs('mb');

            //clean up string
            $source = mb_strtolower(mb_trim(mb_strip_tags($source)));

            //convert spanish crap to english
            $source2 = str_convert_accents($source);

            //remove special chars
            $from = array("'", '"', '\\');
            $to = array('', '', '');
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
            $source2 = str_convert_accents($source);

            //remove special chars
            $from = array("'", '"', '\\');
            $to = array('', '', '');
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


    /*
     * Here be wrapper functions
     * DO NOT USE THESE, THESE FUNCTIONS ARE DEPRECATED AND WILL BE DROPPED IN THE NEAR FUTURE!!
     */
    function seo_create_string($source, $replace = '-')
    {
        try {
            return seo_string($source, $replace = '-');

        } catch (Exception $e) {
            throw new OutOfBoundsException('seo_string(): Failed', $e);
        }
    }

    function seo_generate_unique_name($source, $table, $ownid = null, $field = 'seoname', $replace = '-', $first_suffix = null)
    {
        try {
            return seo_unique($source, $table, $ownid, $field, $replace, $first_suffix);

        } catch (Exception $e) {
            throw new OutOfBoundsException('seo_generate_unique_name(): Failed', $e);
        }
    }



    /**
     * @param $source
     * @param $table
     * @param $ownid
     * @param $field
     * @param $replace
     * @param $first_suffix
     * @return mixed|string|null
     */
    public function uniqueString($source, $table, $ownid = null, $field = 'seoname', $replace = '-', $first_suffix = null)
    {
        try {
            return seo_unique($source, $table, $ownid, $field, $replace, $first_suffix);

        } catch (Exception $e) {
            throw new OutOfBoundsException('seo_unique_string(): Failed', $e);
        }
    }
}
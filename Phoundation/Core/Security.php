<?php

namespace Phoundation\Core;

/**
 * Class Security
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Core
 */
class Security
{
//
//    /*
//     * Calculate the hash value for the given password with the (possibly) given
//     * algorithm
//     */
//    function password($source, $algorithm, $add_meta = true)
//    {
//        return get_hash($source, $algorithm, $add_meta);
//    }
//
//    function get_hash($source, $algorithm, $add_meta = true)
//    {
//        global $_CONFIG;
//
//        try {
//            try {
//                $source = hash($algorithm, SEED . $source);
//
//            } catch (Exception $e) {
//                if (strstr($e->getMessage(), 'Unknown hashing algorithm')) {
//                    throw new OutOfBoundsException(tr('get_hash(): Unknown hash algorithm ":algorithm" specified', array(':algorithm' => $algorithm)), 'unknown-algorithm');
//                }
//
//                throw $e;
//            }
//
//            if ($add_meta) {
//                return '*' . $algorithm . '*' . $source;
//            }
//
//            return $source;
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException('get_hash(): Failed', $e);
//        }
//    }
//
//


    /*
     * Return a code that is guaranteed unique
     */
    function unique_code($hash = 'sha512')
    {
        global $_CONFIG;

        try {
            return hash($hash, uniqid('', true) . microtime(true) . $_CONFIG['security']['seed']);

        } catch (Exception $e) {
            throw new OutOfBoundsException('unique_code(): Failed', $e);
        }
    }


}
<?php

namespace Phoundation\Databases;



/**
 * Class MemcachedNamespace
 *
 * This is the default MemcachedNamespace object
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class MemcachedNamespace
{
    /**
     * @var Mc|null
     */
    protected ?Mc $mc = null;



    /**
     * MemcachedNamespace Constructor
     *
     * @param Mc $mc
     */
    public function __construct(Mc $mc)
    {
        $this->mc = $mc;
    }



    /**
     * Return the key for the specified key and namespace combination
     *
     * @param string $key
     * @param string|null $namespace
     * @return string
     */
    public function getKey(string $key, ?string $namespace = null): string
    {
//        /**
//         * Return a key for the namespace. We don't use the namespace itself as part of the key because
//         * with an alternate key, its very easy to invalidate namespace keys by simply assigning a new
//         * value to the namespace key
//         */
//        public function namespace($namespace, $delete = false)
//    {
//        global $_CONFIG;
//        static $keys = array();
//
//        try {
//            if (!$namespace or !$_CONFIG['memcached']['namespaces']) {
//                return '';
//            }
//
//            if (array_key_exists($namespace, $keys)) {
//                return $keys[$namespace];
//            }
//
//            $key = memcached_get('ns:' . $namespace);
//
//            if (!$key) {
//                $key = (string)microtime(true);
//                memcached_add($key, 'ns:' . $namespace);
//
//            } elseif ($delete) {
//                /*
//                 * "Delete" the key by incrementing (and so, changing) the value of the namespace key.
//                 * Since this will change the name of all keys using this namespace, they are no longer
//                 * accessible and with time will be dumped automatically by memcached to make space for
//                 * newer keys.
//                 */
//                try {
//                    memcached_increment($namespace);
//                    $key = memcached_get('ns:' . $namespace);
//
//                } catch (Exception $e) {
//                    /*
//                     * Increment failed, so in all probability the key did not exist. It could have been
//                     * deleted by a parrallel process, for example
//                     */
//                    switch ($e->getCode()) {
//                        case '':
//                            // :TODO: Implement correctly. For now, just notify
//                        default:
//                            notify($e);
//                    }
//                }
//            }
//
//            $keys[$namespace] = $key;
//            return $key;
//
//        } catch (Exception $e) {
//            throw new MemcachedException('memcached_namespace(): Failed', $e);
//        }
//    }
//

        return $this->mc->getConfiguration('prefix') . $key;
    }



    /**
     *
     * @todo implement
     * @return void
     */
    public function delete(string $namespace): void
    {

    }
}
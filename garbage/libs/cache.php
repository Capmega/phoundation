<?php
/*
 * Cache library
 *
 * This library contains caching functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package cache
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
 * @package cache
 *
 * @return void
 */
function cache_library_init() {
    global $_CONFIG;

    try {
        /*
         * Auto load the memcached or file library
         */
        switch ($_CONFIG['cache']['method']) {
            case 'memcached':
                load_libs('memcached');
                break;

            case 'file':
                break;

            case false:
                /*
                 * Cache has been disabled
                 */
                return false;

            default:
                throw new CoreException(tr('Unknown cache method ":method" specified', array(':method' => $_CONFIG['cache']['method'])), 'unknown');
        }


    }catch(Exception $e) {
        throw new CoreException('cache_library_init(): Failed', $e);
    }
}



/*
 * Read blob for the specified $key from cache
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cache
 * @see cache_read_file()
 * @see cache_write()
 * @version 2.8.46: Added documentation
 *
 * @param string $key
 * @param null string $namespace
 * @return mixed The cache blob data if found, null otherwise
 */
function cache_read($key, $namespace = null) {
    global $_CONFIG, $core;

    try {
        if (!$key) {
            throw new CoreException(tr('cache_read(): No cache key specified'), 'not-specified');
        }

        switch ($_CONFIG['cache']['method']) {
            case 'file':
                $key  = cache_key_hash($key);
                $data = cache_read_file($key, $namespace);
                break;

            case 'memcached':
                if ($namespace) {
                    $namespace = Strings::unslash($namespace);
                }

                $data = memcached_get($key, $namespace);
                break;

            case false:
                /*
                 * Cache has been disabled
                 */
                return false;

            default:
                throw new CoreException(tr('cache_read(): Unknown cache method ":method" specified', array(':method' => $_CONFIG['cache']['method'])), 'unknown');
        }

        if ($data) {
            log_console(tr('Found cache blob for key ":namespace-:key"', array(':namespace' => $namespace, ':key' => $key)), 'VERBOSE/green');
        }

        return $data;

    }catch(Exception $e) {
        throw new CoreException('cache_read(): Failed', $e);
    }
}



/*
 * Read blob for the specified $key from cache file. File must exist and not have filemtime + max_age > now
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cache
 * @see cache_read()
 * @see cache_write_file()
 * @version 2.8.46: Added documentation
 *
 * @param string $key
 * @param null string $namespace
 * @return mixed The cache blob data if found, null otherwise
 */
function cache_read_file($key, $namespace = null) {
    global $_CONFIG;

    try {
        if ($namespace) {
            $namespace = Strings::slash($namespace);
        }

        if (!file_exists($file = PATH_ROOT.'data/cache/'.$namespace.$key)) {
            return false;
        }

//show((filemtime($file) + $_CONFIG['cache']['max_age']));
//showdie(date('u'));
        if ((filemtime($file) + $_CONFIG['cache']['max_age']) < date('u')) {
            return false;
        }

        return file_get_contents($file);

    }catch(Exception $e) {
        throw new CoreException('cache_read_file(): Failed', $e);
    }
}



/*
 * Write specified data blob to cache with the specified $key
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cache
 * @see cache_read()
 * @see cache_write_file()
 * @version 2.8.46: Added documentation
 *
 * @param mixed $value
 * @param string $key
 * @param null string $namespace
 * @return mixed The cache blob data if found, null otherwise
 */
function cache_write($value, $key, $namespace = null, $max_age = null) {
    global $_CONFIG, $core;

    try {
        if (!$max_age) {
            $max_age = $_CONFIG['cache']['max_age'];
        }

        if (!$key) {
            throw new CoreException(tr('cache_write(): No cache key specified'), 'warning/not-specified');
        }

        switch ($_CONFIG['cache']['method']) {
            case 'file':
                $key = cache_key_hash($key);
                cache_write_file($value, $key, $namespace);
                break;

            case 'memcached':
                memcached_put($value, $key, $namespace, $max_age);
                break;

            case false:
                /*
                 * Cache has been disabled
                 */
                return $value;

            default:
                throw new CoreException(tr('cache_write(): Unknown cache method ":method" specified', array(':method' => $_CONFIG['cache']['method'])), 'unknown');
        }

        log_console(tr('Wrote cache blob for key ":namespace-:key"', array(':namespace' => $namespace, ':key' => $key)), 'VERBOSE/green');
        return $value;

    }catch(Exception $e) {
        /*
         * Cache failed to write. Lets not die on this!
         *
         * Notify and continue without the cache
         */
        notify($e);
        return $value;
    }
}



/*
 * Write specified data blob to cache file with the specified $key
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cache
 * @see cache_read_file()
 * @see cache_write()
 * @version 2.8.46: Added documentation
 *
 * @param mixed $value
 * @param string $key
 * @param null string $namespace
 * @return mixed The cache blob data if found, null otherwise
 */
function cache_write_file($value, $key, $namespace = null) {
    try {
        if ($namespace) {
            $namespace = Strings::slash($namespace);
        }

        $file = PATH_ROOT.'data/cache/'.$namespace.$key;

        Path::ensure(dirname($file), 0770);
        file_put_contents($file, $value);
        chmod($file, 0660);

        return $value;

    }catch(Exception $e) {
        throw new CoreException('cache_write_file(): Failed', $e);
    }
}



/*
 * Return a hashed key
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cache
 * @see cache_read()
 * @see cache_write()
 * @version 2.8.46: Added documentation
 *
 * @param string $key
 * @return string A hash key
 */
function cache_key_hash($key) {
    global $_CONFIG;

    try {
        try {
            get_hash($key, $_CONFIG['cache']['key_hash']);

        }catch(Exception $e) {
            throw new CoreException(tr('Unknown key hash algorithm ":algorithm" configured in $_CONFIG[hash][key_hash]', array(':algorithm' => $_CONFIG['cache']['key_hash'])), $e);
        }

        if ($_CONFIG['cache']['key_interlace']) {
            $interlace = substr($key, 0, $_CONFIG['cache']['key_interlace']);
            $key       = substr($key, $_CONFIG['cache']['key_interlace']);

            return str_interleave($interlace, '/').'/'.$key;
        }

        return $key;

    }catch(Exception $e) {
        throw new CoreException('cache_key_hash(): Failed', $e);
    }
}



/*
 *
 */
function cache_showpage($key = null, $namespace = 'htmlpage', $etag = null) {
    global $_CONFIG, $core;

    try {
        if ($_CONFIG['cache']['method']) {
            /*
             * Default values
             */
            if (!$key) {
                $key = $core->register['script'];
            }

            if (!$etag) {
                $etag = isset_get($core->register['etag']);
            }

            Core::readRegister('page_cache_key', $key);

            /*
             * First try to apply HTTP ETag cache test
             */
            http_cache_test($etag);

            if ($value = cache_read($key, $namespace)) {
                http_headers(null, strlen($value));

                echo $value;
                die();
            }
        }

        return false;

    }catch(Exception $e) {
        throw new CoreException('cache_showpage(): Failed', $e);
    }
}



/*
 * Clear the entire cache
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cache
 * @see cache_read()
 * @see cache_write()
 * @see cache_size()
 * @see cache_count()
 * @version 2.8.46: Added documentation
 *
 * @param string $key
 * @param string $namespace
 * @return void
 */
function cache_clear($key = null, $namespace = null) {
    include('handlers/cache-clear.php');
}



/*
 * Return the total size of the cache
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cache
 * @see cache_read()
 * @see cache_write()
 * @see cache_clear()
 * @see cache_count()
 * @version 2.8.46: Added documentation
 *
 * @param string $key
 * @param string $namespace
 * @return natural The size of the cache in bytes
 */
function cache_size() {
    return include('handlers/cache-size.php');
}



/*
 * Return the total amount of files currently in cache
  *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cache
 * @see cache_read()
 * @see cache_write()
 * @see cache_clear()
 * @see cache_size()
 * @version 2.8.46: Added documentation
 *
 * @param string $key
 * @param string $namespace
 * @return natural The number of objects available in cache
 */
function cache_count() {
    return include('handlers/cache-count.php');
}
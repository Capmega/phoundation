<?php
/*
 * Cache library
 *
 * This library contains caching functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package cache
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cache
 *
 * @return void
 */
function cache_library_init(){
    global $_CONFIG;

    try{
        /*
         * Auto load the memcached or file library
         */
        switch($_CONFIG['cache']['method']){
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
                throw new BException(tr('Unknown cache method ":method" specified', array(':method' => $_CONFIG['cache']['method'])), 'unknown');
        }


    }catch(Exception $e){
        throw new BException('cache_library_init(): Failed', $e);
    }
}



/*
 * Read from cache
 */
function cache_read($key, $namespace = null){
    global $_CONFIG, $core;

    try{
        if(!$key){
            throw new BException(tr('cache_read(): No cache key specified'), 'not-specified');
        }

        switch($_CONFIG['cache']['method']){
            case 'file':
                $key  = cache_key_hash($key);
                $data = cache_read_file($key, $namespace);
                break;

            case 'memcached':
                if($namespace){
                    $namespace = unslash($namespace);
                }

                $data = memcached_get($key, $namespace);
                break;

            case false:
                /*
                 * Cache has been disabled
                 */
                return false;

            default:
                throw new BException(tr('cache_read(): Unknown cache method ":method" specified', array(':method' => $_CONFIG['cache']['method'])), 'unknown');
        }

        return $data;

    }catch(Exception $e){
        throw new BException('cache_read(): Failed', $e);
    }
}



/*
 * Read from cache file.
 * File must exist and not have filemtime + max_age > now
 */
function cache_read_file($key, $namespace = null){
    global $_CONFIG;

    try{
        if($namespace){
            $namespace = slash($namespace);
        }

        if(!file_exists($file = ROOT.'data/cache/'.$namespace.$key)){
            return false;
        }

//show((filemtime($file) + $_CONFIG['cache']['max_age']));
//showdie(date('u'));
        if((filemtime($file) + $_CONFIG['cache']['max_age']) < date('u')){
            return false;
        }

        return file_get_contents($file);

    }catch(Exception $e){
        throw new BException('cache_read_file(): Failed', $e);
    }
}



/*
 * Read to cache
 */
function cache_write($value, $key, $namespace = null, $max_age = null){
    global $_CONFIG, $core;

    try{
        if(!$max_age){
            $max_age = $_CONFIG['cache']['max_age'];
        }

        if(!$key){
            throw new BException(tr('cache_write(): No cache key specified'), 'warning/not-specified');
        }

        switch($_CONFIG['cache']['method']){
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
                throw new BException(tr('cache_write(): Unknown cache method ":method" specified', array(':method' => $_CONFIG['cache']['method'])), 'unknown');
        }

        return $value;

    }catch(Exception $e){
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
 * Write to cache file
 */
function cache_write_file($value, $key, $namespace = null){
    try{
        if($namespace){
            $namespace = slash($namespace);
        }

        $file = ROOT.'data/cache/'.$namespace.$key;

        file_ensure_path(dirname($file), 0770);
        file_put_contents($file, $value);
        chmod($file, 0660);

        return $value;

    }catch(Exception $e){
        throw new BException('cache_write_file(): Failed', $e);
    }
}



/*
 * Return a hashed key
 */
function cache_key_hash($key){
    global $_CONFIG;

    try{
        try{
            get_hash($key, $_CONFIG['cache']['key_hash']);

        }catch(Exception $e){
            throw new BException(tr('Unknown key hash algorithm ":algorithm" configured in $_CONFIG[hash][key_hash]', array(':algorithm' => $_CONFIG['cache']['key_hash'])), $e);
        }

        if($_CONFIG['cache']['key_interlace']){
            $interlace = substr($key, 0, $_CONFIG['cache']['key_interlace']);
            $key       = substr($key, $_CONFIG['cache']['key_interlace']);

            return str_interleave($interlace, '/').'/'.$key;
        }

        return $key;

    }catch(Exception $e){
        throw new BException('cache_key_hash(): Failed', $e);
    }
}



/*
 *
 */
function cache_showpage($key = null, $namespace = 'htmlpage', $etag = null){
    global $_CONFIG, $core;

    try{
        if($_CONFIG['cache']['method']){
            /*
             * Default values
             */
            if(!$key){
                $key = $core->register['script'];
            }

            if(!$etag){
                $etag = isset_get($core->register['etag']);
            }

            $core->register('page_cache_key', $key);

            /*
             * First try to apply HTTP ETag cache test
             */
            http_cache_test($etag);

            if($value = cache_read($key, $namespace)){
                http_headers(null, strlen($value));

                echo $value;
                die();
            }
        }

        return false;

    }catch(Exception $e){
        throw new BException('cache_showpage(): Failed', $e);
    }
}



/*
 * Clear the entire cache
 */
function cache_clear($key = null, $namespace = null){
    include('handlers/cache-clear.php');
}



/*
 * Return the total size of the cache
 */
function cache_size(){
    return include('handlers/cache-size.php');
}



/*
 * Return the total amount of files currently in cache
 */
function cache_count(){
    return include('handlers/cache-count.php');
}



/*
 * Return true if the file exists in cache and has not expired
 * Return false if the file does not exist, or was expired
 * If the file does exsit, but is expired, delete it to auto cleanup cache
 */
function cache_has_file($file, $max_age = null){
    global $_CONFIG;

    try{
        if(!$max_age){
            $max_age = $_CONFIG['cache']['max_age'];
        }

        if(!file_exists($file)){
            return false;
        }

        $mtime = filemtime($file);

        if((time() - $mtime) > $max_age){
            /*
             *
             */
            file_delete($file);
            return false;
        }

        return true;

    }catch(Exception $e){
        throw new BException(tr('cache_has_file(): Failed'), $e);
    }
}
?>

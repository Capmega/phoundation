<?php
/*
 * Clear all cache or portions of the cache
 */
global $_CONFIG;

try{
    /*
     * Clear normal cache
     */
    switch($_CONFIG['cache']['method']){
        case 'file':
            if($key){
                $key = cache_key_hash($key);
            }

            if($namespace){
                if($key){
                    /*
                     * Delete only one cache file, and attempt to clear empty directories as possible
                     */
                    file_clear_path(ROOT.'data/cache/'.slash($namespace).$key, ROOT.'data/cache');

                }else{
                    /*
                     * Delete specified group
                     */
                    file_delete(ROOT.'data/cache/'.$namespace, ROOT.'data/cache');
                }

            }elseif($key){
                /*
                 * Delete only one cache file, and attempt to clear empty directories as possible
                 */
                file_clear_path(ROOT.'data/cache/'.$key, ROOT.'data/cache');

            }else{
                /*
                 * Delete all cache
                 */
                safe_exec(array('commands' => array('rm', array(ROOT.'/data/cache/', '-rf'))));
            }

            file_ensure_path(ROOT.'data/cache');
            log_console(tr('Cleared file caches from path ":path"', array(':path' => 'ROOT/data/cache')));
            break;

        case 'memcached':
            /*
             * Clear all keys from memcached
             */
            if($namespace){
                memcached_delete(null, $namespace);

            }elseif($key){
                memcached_delete($key, $namespace);

            }else{
                memcached_clear();
            }

            log_console(tr('Cleared memchached caches from servers ":servers"', array(':servers' => $_CONFIG['memcached']['servers'])));
            break;

        case false:
            /*
             * Cache has been disabled, ignore
             */
            break;

        default:
            throw new BException(tr('cache_clear(): Unknown cache method ":method" specified', array('%method' => $_CONFIG['cache']['method'])), 'unknown');
    }

}catch(Exception $e){
    notify($e);
}



/*
 * Clear CDN and CDN bundler caches
 */
try{
    if(empty($_CONFIG['language']['supported'])){
        $languages = array('en' => tr('English'));

    }else{
        $languages = $_CONFIG['language']['supported'];
    }

    /*
     * Clear cache for all languages
     */
    foreach($languages as $code => $name) {
        /*
         * Delete all cache files
         * Delete all bundle files
         * Delete all purged bundle files
         */
        file_execute_mode(ROOT.'www/'.$code.'/pub/js/', 0770, function() use ($code){
            file_execute_mode(ROOT.'www/'.$code.'/pub/css/', 0770, function() use ($code){
                file_delete(ROOT.'www/'.$code.'/pub/js/cached*'          , ROOT.'www/'.$code.'/pub/js/');
                file_delete(ROOT.'www/'.$code.'/pub/js/bundle-*'         , ROOT.'www/'.$code.'/pub/js/');
                file_delete(ROOT.'www/'.$code.'/pub/css/bundle-*'        , ROOT.'www/'.$code.'/pub/css/');
                file_delete(ROOT.'www/'.$code.'/pub/css/p-bundle-*'      , ROOT.'www/'.$code.'/pub/css/');
            });
        });

        file_execute_mode(ROOT.'www/'.$code.'/admin/pub/js/', 0770, function() use ($code){
            file_execute_mode(ROOT.'www/'.$code.'/admin/pub/css/', 0770, function() use ($code){
                file_delete(ROOT.'www/'.$code.'/admin/pub/js/bundle-*'   , ROOT.'www/'.$code.'/admin/pub/js/');
                file_delete(ROOT.'www/'.$code.'/admin/pub/css/p-bundle-*', ROOT.'www/'.$code.'/admin/pub/css/');
            });
        });

        /*
         * Delete all auto converted webp images
         */
        cli_find(array('start' => ROOT.'data/content/',
                       'name'  => '*.webp',
                       'exec'  => 'delete'));

        if(FORCE){
            /*
             * Delete external / vendor libraries too
             */
            file_delete(ROOT.'node_modules', ROOT);
        }

        log_console(tr('Cleared bundler caches from paths ":path"', array(':path' => 'ROOT/www/'.$code.'/pub/js/bundle-*,ROOT/www/'.$code.'/pub/css/bundle-*')), 'green');
    }

}catch(Exception $e){
    notify($e);
}

log_database('Cleared all caches', 'clearcache');

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
                    file_clear_path(ROOT.'data/cache/'.slash($namespace).$key);

                }else{
                    /*
                     * Delete specified group
                     */
                    file_delete_tree(ROOT.'data/cache/'.$namespace);
                }

            }elseif($key){
                /*
                 * Delete only one cache file, and attempt to clear empty directories as possible
                 */
                file_clear_path(ROOT.'data/cache/'.$key);

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
                mc_delete(null, $namespace);

            }elseif($key){
                mc_delete($key, $namespace);

            }else{
                mc_clear();
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
 * Clear bundler caches
 */
try{
    file_delete(ROOT.'www/en/pub/js/bundle-*'       , false, false, ROOT.'www/en/pub/js/');
    file_delete(ROOT.'www/en/pub/css/bundle-*'      , false, false, ROOT.'www/en/pub/css/');
    file_delete(ROOT.'www/en/admin/pub/js/bundle-*' , false, false, ROOT.'www/en/admin/pub/js/');
    file_delete(ROOT.'www/en/admin/pub/css/bundle-*', false, false, ROOT.'www/en/admin/pub/css/');

    log_console(tr('Cleared bundler caches from paths ":path"', array(':path' => 'ROOT/pub/js/bundle-*,ROOT/pub/css/bundle-*')), 'green');

}catch(Exception $e){
    notify($e);
}

log_database('Cleared all caches', 'clearcache');
?>

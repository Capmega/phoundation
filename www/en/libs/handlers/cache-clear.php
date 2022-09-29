<?php
/*
 * Clear all cache or portions of the cache
 */
global $_CONFIG;

try{
    /*
     * Clear normal cache
     */
    load_libs('sql-exists');
    log_console(tr('Clearing all cache'), 'VERBOSE/cyan');

    switch($_CONFIG['cache']['method']) {
        case 'file':
            if ($key) {
                $key = cache_key_hash($key);
            }

            if ($namespace) {
                if ($key) {
                    /*
                     * Delete only one cache file, and attempt to clear empty directories as possible
                     */
                    file_delete(array('patterns'     => ROOT.'data/cache/'.Strings::slash($namespace).$key,
                                      'restrictions' => ROOT.'data/cache/',
                                      'clean_path'   => false));

                } else {
                    /*
                     * Delete specified group
                     */
                    file_delete(array('patterns'     => ROOT.'data/cache/'.$namespace,
                                      'restrictions' => ROOT.'data/cache/',
                                      'clean_path'   => false));
                }

            } elseif ($key) {
                /*
                 * Delete only one cache file, and attempt to clear empty directories as possible
                 */
                file_clear_path(ROOT.'data/cache/'.$key, ROOT.'data/cache/');

            } else {
                /*
                 * Delete all cache
                 */
                file_delete(array('patterns'     => ROOT.'data/cache/',
                                  'restrictions' => ROOT.'data/',
                                  'clean_path'   => false));
            }

            file_ensure_path(ROOT.'data/cache');
            log_console(tr('Cleared file caches from path ":path"', array(':path' => ROOT.'data/cache')), 'green');
            break;

        case 'memcached':
            /*
             * Clear all keys from memcached
             */
            if ($namespace) {
                memcached_delete(null, $namespace);

            } elseif ($key) {
                memcached_delete($key, $namespace);

            } else {
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
            throw new CoreException(tr('cache_clear(): Unknown cache method ":method" specified', array('%method' => $_CONFIG['cache']['method'])), 'unknown');
    }

    /*
     * Clear the tmp directory
     */
    file_delete(array('patterns'    => ROOT.'data/tmp/',
                      'restrictions' => ROOT.'data/',
                      'clean_path'   => false));

    file_ensure_path(ROOT.'data/tmp');
    log_console(tr('Cleared data/tmp'), 'green');

    /*
     * Clear CDN and CDN bundler caches
     */
    if (empty($_CONFIG['language']['supported'])) {
        $languages = array('en' => tr('English'));

    } else {
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
        if (!file_exists(ROOT.'www/'.$code)) {
            /*
             * This language doesn't have a web directory, ignore it
             */
            log_console(tr('Skipping cache clearing www directory for language ":language" as the directory "www/:code" does not exist', array(':language' => $name, ':code' => $code)), 'yellow');
            continue;
        }

        if (file_exists(ROOT.'www/'.$code.'/pub/js/')) {
            file_execute_mode(ROOT.'www/'.$code.'/pub/js/', 0770, function() use ($code) {
                file_delete(array('patterns'       => ROOT.'www/'.$code.'/pub/js/cached-*,'.ROOT.'www/'.$code.'/pub/js/bundle-*' ,
                                  'restrictions'   => ROOT.'www/'.$code.'/pub/js/',
                                  'force_writable' => true));
            });

            log_console(tr('Cleared javascript path ":path" from bundle and cache files', array(':path' => ROOT.'www/'.$code.'/pub/js/')), 'green');

        } else {
            log_console(tr('Skipping cache clearing path ":path", it does not exists or is not accessible', array(':path' => ROOT.'www/'.$code.'/pub/js/')), 'yellow');
        }

        if (file_exists(ROOT.'www/'.$code.'/pub/css/')) {
            file_execute_mode(ROOT.'www/'.$code.'/pub/css/', 0770, function() use ($code) {
                file_delete(array('patterns'       => ROOT.'www/'.$code.'/pub/css/bundle-*,'.ROOT.'www/'.$code.'/pub/css/p-bundle-*',
                                  'restrictions'   => ROOT.'www/'.$code.'/pub/css/',
                                  'force_writable' => true));
            });

            log_console(tr('Cleared CSS path ":path" from bundle files', array(':path' => ROOT.'www/'.$code.'/pub/css/')), 'green');

        } else {
            log_console(tr('Skipping cache clearing path ":path", it does not exists or is not accessible', array(':path' => ROOT.'www/'.$code.'/pub/css/')), 'yellow');
        }

        if (file_exists(ROOT.'www/'.$code.'/admin/pub/js/')) {
            file_execute_mode(ROOT.'www/'.$code.'/admin/pub/js/', 0770, function() use ($code) {
                file_delete(array('patterns'       => ROOT.'www/'.$code.'/admin/pub/js/cached-*,'.ROOT.'www/'.$code.'/admin/pub/js/bundle-*',
                                  'restrictions'   => ROOT.'www/'.$code.'/admin/pub/js/',
                                  'force_writable' => true));
            });

            log_console(tr('Cleared admin javascript path ":path" from bundle and cache files', array(':path' => ROOT.'www/'.$code.'/admin/pub/js/')), 'green');

        } else {
            log_console(tr('Skipping cache clearing path ":path", it does not exists or is not accessible', array(':path' => ROOT.'www/'.$code.'/admin/pub/js/')), 'yellow');
        }

        if (file_exists(ROOT.'www/'.$code.'/admin/pub/css/')) {
            file_execute_mode(ROOT.'www/'.$code.'/admin/pub/css/', 0770, function() use ($code) {
                file_delete(array('patterns'       => ROOT.'www/'.$code.'/admin/pub/css/bundle-*,'.ROOT.'www/'.$code.'/admin/pub/css/p-bundle-*',
                                  'restrictions'   => ROOT.'www/'.$code.'/admin/pub/css/',
                                  'force_writable' => true));
            });

            log_console(tr('Cleared admin CSS path ":path" from bundle files', array(':path' => ROOT.'www/'.$code.'/admin/pub/css/')), 'green');

        } else {
            log_console(tr('Skipping cache clearing path ":path", it does not exists or is not accessible', array(':path' => ROOT.'www/'.$code.'/admin/pub/css/')), 'yellow');
        }
    }

    /*
     * Delete all auto converted webp images
     */
    foreach(array(ROOT.'data/content/', ROOT.'www/') as $path) {
        if (!file_exists(ROOT.'data/content/')) {
            continue;
        }

        $files = cli_find(array('start' => $path,
                                'name'  => '*.webp'));

        foreach($files as $file) {
            file_execute_mode('*'.dirname($file), 0770, function() use ($file, $path) {
                file_delete($file, $path);
            });
        }

        log_console(tr('Cleared all automatically converted webp files'), 'green');

        /*
         * Delete all auto resized images
         */
        $files = cli_find(array('start' => $path,
                                'regex' => '.+@[0-9]+x[0-9]+\..*'));

        foreach($files as $file) {
            file_execute_mode('*'.dirname($file), 0770, function() use ($file, $path) {
                file_delete($file, $path);
            });
        }

        log_console(tr('Cleared all automatically resized image files'), 'green');
    }

    /*
     * Delete all static routes
     */
    sql_table_exists('routes_static', 'DELETE FROM `routes_static`');
    log_console(tr('Cleared all static routes'), 'green');

    /*
     * Delete external / vendor libraries too
     */
    if (FORCE) {
        if (file_exists(ROOT.'node_modules/')) {
            file_execute_mode('*'.ROOT.'node_modules/', 0770, function() use ($code) {
                file_delete(ROOT.'node_modules/', ROOT);
            });
        }

        log_console(tr('Cleared node_modules path ":path"', array(':path' => ROOT.'node_modules/')), 'green');
    }

}catch(Exception $e) {
    $e->addMessages(tr('cache_clear(): Failed'));
    notify($e);
}

log_database('Cleared all caches', 'clearcache');

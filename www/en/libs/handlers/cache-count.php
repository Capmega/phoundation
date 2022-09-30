<?php
/*
 * Return the amount of files currently in cache
 */
global $_CONFIG;

try {
    switch($_CONFIG['cache']['method']) {
        case 'file':
            load_libs('numbers');
            file_ensure_path(ROOT.'data/cache');
            return file_tree(ROOT.'data/cache', 'count');

        case 'memcached':
// :IMPLEMENT:
            break;

        case false:
            /*
             * Cache has been disabled
             */
            throw new CoreException(tr('cache_count(): Can not count cache objects, cache has been disabled'), 'disabled');

        default:
            throw new CoreException(tr('cache_count(): Unknown cache method "%method%" specified', array('method' => str_log($_CONFIG['cache']['method']))), 'unknown');
    }

}catch(Exception $e) {
    throw new CoreException('cache_count(): Failed', $e);
}
?>
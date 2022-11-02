<?php
/*
 * Return the size of the cache
 */
global $_CONFIG;

try {
    switch ($_CONFIG['cache']['method']) {
        case 'file':
            load_libs('numbers');
            Path::ensure(PATH_ROOT.'data/cache');
            return file_tree(PATH_ROOT.'data/cache', 'size');

        case 'memcached':
// :IMPLEMENT:
            break;

        case false:
            /*
             * Cache has been disabled
             */
            throw new CoreException(tr('cache_size(): Can not size for cache objects, cache has been disabled'), 'disabled');

        default:
            throw new CoreException(tr('cache_size(): Unknown cache method "%method%" specified',array('method' => Strings::Log($_CONFIG['cache']['method']))), 'unknown');
    }

}catch(Exception $e) {
    throw new CoreException('cache_size(): Failed', $e);
}
?>
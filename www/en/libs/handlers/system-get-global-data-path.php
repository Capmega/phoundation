<?php
global $_CONFIG;

try {
    /*
     * First find the global data path. For now, either same height as this
     * project, OR one up the filesystem tree
     */
    $paths = array('/var/lib/data/',
                   '/var/www/data/',
                   ROOT.'../data/',
                   ROOT.'../../data/'
                   );

    if (!empty($_SERVER['HOME'])) {
        /*
         * Also check the users home directory
         */
        $paths[] = $_SERVER['HOME'].'/projects/data/';
        $paths[] = $_SERVER['HOME'].'/data/';
    }

    $found = false;

    foreach($paths as $path) {
        if (file_exists($path)) {
            $found = $path;
            break;
        }
    }

    if ($found) {
        /*
         * Cleanup path. If realpath fails, we know something is amiss
         */
        if (!$found = realpath($found)) {
            throw new CoreException('get_global_data_path(): Found path "'.$path.'" failed realpath() check', 'path-failed');
        }
    }

    if (!$found) {
        if (!PLATFORM_CLI) {
            throw new CoreException('get_global_data_path(): Global data path not found', 'not-exists');
        }

        try {
            log_console('Warning: Global data path not found. Normally this path should exist either 1 directory up, 2 directories up, in /var/lib/data, /var/www/data, $USER_HOME/projects/data, or $USER_HOME/data', 'yellow');
            log_console('Warning: If you are sure this simply does not exist yet, it can be created now automatically. If it should exist already, then abort this script and check the location!', 'yellow');

            $path = script_exec(array('commands' => array('base/init_global_data_path')));

            if (!file_exists($path)) {
                /*
                 * Something went wrong and it was not created anyway
                 */
                throw new CoreException('get_global_data_path(): ./script/base/init_global_data_path reported path "'.str_log($path).'" was created but it could not be found', 'failed');
            }

            /*
             * Its now created!
             * Strip "data/"
             */
            $path = Strings::slash($path);

        }catch(Exception $e) {
            throw new CoreException('get_global_data_path(): Global data path not found, or init_global_data_path failed / aborted', $e);
        }
    }

    /*
     * Now check if the specified section exists
     */
    if ($section and !file_exists($path.$section)) {
        file_ensure_path($path.$section);
    }

    if ($writable and !is_writable($path.$section)) {
        throw new CoreException(tr('The global path ":path" is not writable', array(':path' => $path.$section)), 'not-writable');
    }

    if (!$global_path = realpath($path.$section)) {
        /*
         * Curious, the path exists, but realpath failed and returned false..
         * This should never happen since we ensured the path above! This is just an extra check in case of.. weird problems :)
         */
        throw new CoreException('The found global data path "'.str_log($path).'" is invalid (realpath returned false)', 'invalid');
    }

    return Strings::slash($global_path);

}catch(CoreException $e) {
    throw new CoreException('get_global_data_path(): Failed', $e);
}
?>

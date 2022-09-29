<?php
/*
 * Snap library
 *
 * This library is used to install packages using snap
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package snap
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
 * @package snap
 *
 * @return void
 */
function snap_library_init() {
    try{
        load_libs('servers');

    }catch(Exception $e) {
        throw new CoreException('snap_library_init(): Failed', $e);
    }
}



/*
 * Install the specified snap packages on the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package snap
 * @version 2.0.3: Added documentation
 * @example
 * code
 * $result = snap_install('go');
 * /code
 *
 * This would install the go package
 *
 * @param string $packages A string delimited list of packages to be installed
 * @param mixed $server
 * @return string The output from the snap-get install command
 */
function snap_install($packages, $server = null) {
    try{
        $packages  = Arrays::force($packages);
        $arguments = array_merge(array('sudo' => true, 'install', '--classic'), Arrays::force($packages, ' '));

        return servers_exec($server, array('timeout'  => 180,
                                           'function' => (PLATFORM_CLI ? 'passthru' : 'exec'),
                                           'commands' => array('snap', $arguments)));

    }catch(Exception $e) {
        throw new CoreException(tr('Failed'), $e);
    }
}
?>

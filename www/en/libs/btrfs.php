<?php
/*
 * BTRFS library
 *
 * This is a front-end library to interact with the Linux Butter File System (short BTRFS) management interface
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package btrfs
 * @dependency btrfs-tools package
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
 * @package btrfs
 *
 * @return void
 */
function btrfs_library_init() {
    try{
        ensure_installed(array('name'     => 'btrfs',
                               'callback' => 'btrfs_install',
                               'which'    => '/bin/btrfs'));

    }catch(Exception $e) {
        throw new CoreException('btrfs_library_init(): Failed', $e);
    }
}



/*
 * Automatically install dependencies for the btrfs library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package btrfs
 * @see btrfs_init_library()
 * @version 2.0.3: Added function and documentation
 * @note This function typically gets executed automatically by the btrfs_library_init() through the ensure_installed() call, and does not need to be run manually
 *
 * @param params $params
 * @return void
 */
function btrfs_install() {
    try{
        load_libs('linux');
        linux_install_package(null, 'btrfs-tools');

    }catch(Exception $e) {
        throw new CoreException('btrfs_install(): Failed', $e);
    }
}



/*
 * Defragment the specified BTRFS path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package btrfs
 * @see btrfs_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @note: This function can be executed on remote servers by specifying $params[server]
 * @version 1.27.0: Added function and documentation
 *
 * @param params $params The btrfs defragment parameters
 * @params string $params[path]
 * @params null mixed $params[server]
 * @params null mixed $params[verbose]
 * @return array The output lines from the "btrfs filesystem defrag" command
 */
function btrfs_defragment($params) {
    try{
        Arrays::ensure($params, 'verbose,path,server');

        if ($params['verbose'] or VERBOSE) {
            $verbose = '-v';

        } else {
            $verbose = '';
        }

        $results = servers_exec($params['server'], array('commands' => array('btrfs', array('filesystem', 'defragment', $version, $params['path']))));
        return $results;

    }catch(Exception $e) {
        throw new CoreException('btrfs_defragment(): Failed', $e);
    }
}



/*
 * Create a BTRFS subvolume at the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package btrfs
 * @see btrfs_snapshot_subvolume()
 * @note: This function can be executed on remote servers by specifying $params[server]
 * @version 1.27.0: Added function and documentation
 *
 * @param params $params The btrfs defragment parameters
 * @params string $params[path]
 * @params null mixed $params[server]
 * @params null mixed $params[verbose]
 * @return array The output lines from the "btrfs subvolume create" command
 */
function btrfs_create_subvolume($params) {
    try{

    }catch(Exception $e) {
        throw new CoreException('btrfs_create_subvolume(): Failed', $e);
    }
}



/*
 * Create a BTRFS subvolume snapshot at the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package btrfs
 * @see btrfs_create_subvolume()
 * @note: This function can be executed on remote servers by specifying $params[server]
 * @version 1.27.0: Added function and documentation
 *
 * @param params $params The btrfs defragment parameters
 * @params string $params[path]
 * @params null mixed $params[server]
 * @params null mixed $params[verbose]
 * @return array The output lines from the "btrfs subvolume snapshot" command
 */
function btrfs_snapshot_subvolume($params) {
    try{

    }catch(Exception $e) {
        throw new CoreException('btrfs_snapshot_subvolume(): Failed', $e);
    }
}
?>

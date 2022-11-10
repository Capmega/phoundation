<?php
/*
 * PHP Node library
 *
 * This library contains all required functions to work with node
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package node
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @version 2.6.14: Added function and documentation
 * @category Function reference
 * @package node
 *
 * @return void
 */
function node_library_init() {
    try {
        ensure_installed(array('name'     => 'node',
                               'callback' => 'node_setup',
                               'which'    => array('nodejs')));

        ensure_installed(array('name'     => 'node',
                               'callback' => 'node_setup_npm',
                               'which'    => array('npm')));

    }catch(Exception $e) {
        throw new CoreException('node_library_init(): Failed', $e);
    }
}



/*
 * Automatically install node
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package node
 * @see node_setup_npm()
 * @version 2.6.14: Added function and documentation
 * @note This function typically gets executed automatically by the node_library_init() through the ensure_installed() call, and does not need to be run manually
 *
 * @return void
 */
function node_setup() {
    try {
        load_libs('linux');
        linux_install_package(null, 'nodejs');

    }catch(Exception $e) {
        throw new CoreException('node_setup(): Failed', $e);
    }
}



/*
 * Automatically install the node npm library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package node
 * @see node_setup()
 * @version 2.6.14: Added function and documentation
 * @note This function typically gets executed automatically by the node_library_init() through the ensure_installed() call, and does not need to be run manually
 *
 * @return void
 */
function node_setup_npm() {
    try {
        load_libs('linux');
        linux_install_package(null, 'npm');

    }catch(Exception $e) {
        throw new CoreException('node_setup_npm(): Failed', $e);
    }
}



/*
 * Execute the specified node command in a shell
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package node
 * @see node_find()
 * @version 2.6.30: Added function and documentation
 *
 * @return void
 */
function node_exec($command, $arguments) {
    try {
        return safe_exec(array('commands' => array('cd'         , array(PATH_ROOT.'node_modules/.bin'),
                                                   './'.$command, $arguments)));

    }catch(Exception $e) {
        throw new CoreException('node_exec(): Failed', $e);
    }
}



/*
 * Check if node is installed and available
 */
function node_find() {
    global $core;

    try {
        try {
            $core->register['node'] = file_which('nodejs');

        }catch(Exception $e) {
            /*
             * No "nodejs"? Maybe just "node" ?
             */
            $core->register['node'] = file_which('node');
        }

        log_console(tr('Using node ":result"', array(':result' => $core->register['node'])), 'green');

    }catch(Exception $e) {
        if ($e->getCode() == 1) {
            throw new CoreException('node_find(): Failed to find a node installation on this computer for this user. On Ubuntu, install node with "sudo apt-get install nodejs"', 'node_not_installed');
        }

        if ($e->getCode() == 'node_modules_path_not_found') {
            throw $e;
        }

        throw new CoreException('node_find(): Failed', $e);
    }
}



/*
 * Check if node is installed and available
 */
function node_find_modules() {
    global $core;

    try {
        log_console('node_find_modules(): Checking node_modules availability', 'white');

        /*
         * Find node_modules path
         */
        if (!$home = getenv('HOME')) {
            throw new CoreException('node_find_modules(): Environment variable "HOME" not found, failed to locate users home directory', 'environment_variable_not_found');
        }

        $home  = Strings::slash($home);
        $found = false;

        /*
         * Search for node_modules path
         */
        foreach (array(PATH_ROOT, getcwd(), $home) as $path) {
            if ($found) {
                break;
            }

            foreach (array('node_modules', '.node_modules') as $subpath) {
                if (file_exists(Strings::slash($path).$subpath)) {
                    $found = Strings::slash($path).$subpath;
                    break;
                }
            }
        }

        if (!$found) {
            /*
             * Initialize the node_modules path
             */
            File::new()->executeMode(PATH_ROOT, 0770, function() use (&$found) {
                log_console(tr('node_find_modules(): node_modules path not found, initializing now with PATH_ROOT/node_modules'), 'yellow');

                $found = PATH_ROOT.'node_modules/';
                Path::ensure($found, 0550);
            });
        }

        log_console(tr('node_find_modules(): Using node_modules ":path"', array(':path' => $found)), 'green');
        $core->register['node_modules'] = Strings::slash($found);

        /*
         * Delete the package-lock file if there
         */
// :TODO: Improve this part. If the package-lock file exists, that means that a node install at least WAS busy, or still is busy in perhaps a parrallel process? Check if node is active, if not THEN delete and continue
        if (file_exists(Strings::slash(dirname($found)).'package-lock.json')) {
            File::new()->executeMode(Strings::slash(dirname($found)), 0770, function() use ($found) {
                /*
                 * Delete the package-lock.json file. It's okay to use the
                 * variable dirname($found) here for restrictions as $found can
                 * be only one of PATH_ROOT, CWD, or the users home directory, and we
                 * are specifically deleting the package-lock.json file
                 */
                file_chmod(array('path'         => Strings::slash(dirname($found)).'package-lock.json',
                                 'mode'         => 0660,
                                 'restrictions' => dirname($found)));

                file_delete(Strings::slash(dirname($found)).'package-lock.json', dirname($found));
            });
        }

    }catch(Exception $e) {
        if ($e->getCode() == 1) {
            throw new CoreException('node_find_modules(): Failed to find a node installation on this computer for this user', 'not_installed');
        }

        if ($e->getCode() == 'path_not_found') {
            throw $e;
        }

        throw new CoreException('node_find_modules(): Failed', $e);
    }
}



/*
 * Check if npm is installed and available
 */
function node_find_npm() {
    global $core;

    try {
        $core->register['npm'] = file_which('npm');
        log_console(tr('Using npm ":result"', array(':result' => $core->register['npm'])), 'green');

    }catch(Exception $e) {
        if ($e->getCode() == 1) {
            throw new CoreException('node_find_npm(): Failed to find an npm installation on this computer for this user. On Ubuntu, install with "sudo apt-get install npm"', 'npm_not_installed');
        }

        throw new CoreException('node_find_npm(): Failed', $e);
    }
}



/*
 * Install specified packages using npm
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package node
 *
 * @param string list $packages
 * @return natural The amount of installed pacakges
 */
function node_install_npm($packages) {
    try {
        $packages = Arrays::force($packages);

        log_console(tr('node_install_npm(): Installing packages ":packages"', array(':packages' => $packages)), 'VERBOSE/cyan');

        File::new()->executeMode(PATH_ROOT, 0770, function() use ($packages) {
            Path::ensure(PATH_ROOT.'node_modules');

            /*
             * Force everything in the node_modules directory to be writable
             * for updates
             */
            file_chmod(array('path'         => PATH_ROOT.'node_modules',
                             'mode'         => 'ug+w',
                             'recursive'    => true,
                             'restrictions' => false));

            foreach ($packages as $package) {
                log_console(tr('node_install_npm(): Installing packages ":packages"', array(':packages' => $packages)), 'VERYVERBOSE/cyan');

                safe_exec(array('timeout'  => 45,
                                'commands' => array('cd' , array(PATH_ROOT),
                                                    'npm', array('install', '--prefix', PATH_ROOT, $package))));
            }

            /*
             * Force everything in the node_modules directory to always be readonly
             */
            file_chmod(array('path'         => PATH_ROOT.'node_modules',
                             'mode'         => 'ug-w',
                             'recursive'    => true,
                             'restrictions' => false));
        });

        return count($packages);

    }catch(Exception $e) {
        throw new CoreException('node_install_npm(): Failed', $e);
    }
}



/*
 * OBSOLETE WRAPPER FUNCTIONS
 */
function node_check() {
    try {
        node_find();

    }catch(Exception $e) {
        throw new CoreException('node_check(): Failed', $e);
    }
}

function node_check_npm() {
    try {
        node_find_npm();

    }catch(Exception $e) {
        throw new CoreException('node_check_npm(): Failed', $e);
    }
}

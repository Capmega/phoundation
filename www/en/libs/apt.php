<?php
/*
 * Api library
 *
 * This library is used to install packages using apt
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <license@capmega.com>
 * @category Function reference
 * @package apt
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
 * @package apt
 *
 * @return void
 */
function apt_library_init(){
    try{
        load_libs('servers');

    }catch(Exception $e){
        throw new CoreException('apt_library_init(): Failed', $e);
    }
}



/*
 * Install the specified apt packages on the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package apt
 * @version 2.0.3: Added documentation
 * @example
 * code
 * $result = apt_install('axel,git');
 * /code
 *
 * This would install the git and axel packages
 *
 * @param string $packages A string delimited list of packages to be installed
 * @param boolean $auto_update If set to true, apt will first update the local database before trying the install method
 * @param mixed $server
 * @return string The output from the apt-get install command
 */
function apt_install($packages, $auto_update = true, $server = null){
    try{
        if($auto_update){
            apt_update($server = null);
        }

        log_console(tr('Installing packages ":packages" using apt', array(':packages' => $packages)), 'cyan');

        $packages  = array_force($packages);
        $arguments = array_merge(array('sudo' => true, '-y', 'install'), array_force($packages, ' '));

        $results   = servers_exec($server, array('timeout'  => 180,
                                                 'function' => (PLATFORM_CLI ? 'passthru' : 'exec'),
                                                 'commands' => array('apt-get', $arguments)));

        return $results;

    }catch(Exception $e){
        switch($e->getRealCode()){
            case '100':
                /*
                 * Package doesn't exist, proabably
                 *
                 * Check if apt mentioned if its on another system, like snap
                 */
                log_console(tr('apt install failed with ":e"', array(':e' => $e->getMessage())), 'yellow');

                /*
                 * apt-get failed!
                 */
                $data = $e->getData();

                if($data){
                    /*
                     * All apt methods failures
                     */
                    $result = end($data);
                    $result = strtolower(trim($result));

                    if(str_exists($result, 'dpkg was interrupted, you must manually run \'sudo dpkg --configure -a\' to correct the problem')){
                        log_console(tr('apt reported dpkg was interrupted, trying to fix'), 'yellow');

                        /*
                         * Some previous install failed. Repair and retry
                         */
                        try{
                            apt_fix($server);
                            $method($server);

                        }catch(Exception $f){
                            throw new CoreException(tr('apt_install(): apt function ":function" failed, repair failed as well with ":f"', array(':function' => $method.'()', ':f' => $f->getMessage())), $e);
                        }
                    }

                    foreach($data as $line){
                        $match = preg_match('/^Try "snap install ([a-z-_]+)"$/ius', $line, $matches);

                        if($match){
                            /*
                             * The specific package is not available in apt, try
                             * installing it with snap instead
                             */
                            log_console(tr('Package ":package" is not available in apt repositories, but was found in snap repositories. Trying to install using snap', array(':package' => $matches[1])), 'yellow');
                            load_libs('snap');

                            $fixed = true;
                            $snap  = snap_install($matches[1]);
                            $data  = array_merge($data, $snap);
                            continue;
                        }

                        $match = preg_match('/^E: Unable to locate package ([a-z-_]+)$/ius', $line, $matches);

                        if($match){
                            throw new CoreException(tr('apt_install(): The specified apt package ":package" does not exist', array(':package' => $matches[1])), 'not-exists');
                        }
                    }

                    if($fixed){
                        return $data;
                    }
                }
        }

        throw new CoreException(tr('apt_install(): Failed'), $e);
    }
}



/*
 * Run apt update on the specified server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package apt
 * @version 2.0.3: Added documentation
 * @example
 * code
 * $result = apt_install($server);
 * /code
 *
 * This would update the apt database on the specified server
 *
 * @param mixed $server
 * @return string The output from the apt-get update command
 */
function apt_update($server = null){
    try{
        log_console(tr('Updating apt database'), 'cyan');
        $results = servers_exec($server, array('timeout'  => 120,
                                               'function' => (PLATFORM_CLI ? 'passthru' : 'exec'),
                                               'commands' => array('apt-get', array('sudo' => true, 'update'))));
        return $results;

    }catch(Exception $e){
        /*
         * Update failed, this may be a partial fail which
         * happens often, or a complete fail which is a problem.
         * Find out if its partial and if so, continue. Else
         * exception
         */
        $hits = 0;
        $data = $e->getData();

        foreach($data as $line){
            $code = substr($line, 0, 3);
            $code = strtolower($code);

            switch($code){
                case 'get':
                    /*
                     * This was a hit, so we have internet and
                     * all and stuff CAN be downloaded
                     */
                    // FALLTRHOUGH

                case 'hit':
                    /*
                     * This was a hit, so we have internet and
                     * all and stuff CAN be downloaded
                     */
                    $hits++;
                    break;

                case 'ign':
                    /*
                     * This was ignored, so nothing downloaded
                     */
                    break;

                case 'err':
                    /*
                     * This was an error
                     */

                default:
                    /*
                     * No ide what this is but its probably not
                     * a hit
                     */
            }
        }

        if(!$hits){
            throw new CoreException(tr('apt_update(): apt update failed to download all resources, see exception data for more information'), $e);
        }

        /*
         * Some errors but we had hits, so assume apt-get went
         * okay. Log a warning just in case and return the data
         * as if all is normal
         */
        log_console(tr('apt update failed to download some resources, please check "apt update" output and fix this'), 'yellow');
        return $data;
    }
}



/*
 * Runs dpkg --configure -a && apt-get install -f on the specified server to fix a crashed or aborted installation
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package apt
 * @version 2.0.3: Added documentation
 * @example
 * code
 * $result = apt_install('axel,git');
 * /code
 *
 * This would install the git and axel packages
 *
 * @param mixed $server
 * @return string The output from the dpkg --configure -a command
 */
function apt_fix($server = null){
    try{
        log_console(tr('Fixing apt database'), 'yellow');

        $results = servers_exec($server, array('timeout'  => 120,
                                               'function' => (PLATFORM_CLI ? 'passthru' : 'exec'),
                                               'commands' => array('dpkg'   , array('sudo' => true, '--configure', '-a', 'connector' => '&&'),
                                                                   'apt-get', array('sudo' => true, 'install', '-f'))));
        return $results;

    }catch(Exception $e){
        throw new CoreException('apt_fix(): Failed', $e);
    }
}
?>

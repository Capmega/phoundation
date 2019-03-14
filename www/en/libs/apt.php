<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package apt
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
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
        throw new BException('apt_library_init(): Failed', $e);
    }
}



/*
 * Install the specified apt packages on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
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
 * @param mixed $server
 * @return string The output from the apt-get install command
 */
function apt_install($packages, $auto_update = true, $server = null){
    try{
        if($auto_update){
            apt_update($server = null);
        }

        $packages  = array_force($packages);
        $arguments = array_merge(array('sudo' => true, '-y', 'install'), array_force($packages, ' '));

        return servers_exec($server, array('timeout'  => 120,
                                           'function' => 'passthru',
                                           'commands' => array('apt-get', $arguments)));

    }catch(Exception $e){
        apt_handle_exception($e, 'apt_install', $server);
    }
}



/*
 * Run apt update on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
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
        $results = servers_exec($server, array('timeout'  => 120,
                                               'function' => 'passthru',
                                               'commands' => array('apt', array('update'))));
        return $results;

    }catch(Exception $e){
        apt_handle_exception($e, 'apt_update', $server);
    }
}



/*
 * Runs dpkg --configure -a && apt-get install -f on the specified server to fix a crashed or aborted installation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
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
        $results = servers_exec($server, array('timeout'  => 120,
                                               'function' => 'passthru',
                                               'commands' => array('dpkg'   , array('sudo' => true, '--configure', '-a', 'connector' => '&&'),
                                                                   'apt-get', array('sudo' => true, 'install', '-f'))));
        return $results;

    }catch(Exception $e){
        throw new BException('apt_fix(): Failed', $e);
    }
}



/*
 * Run apt update on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
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
function apt_handle_exception($e, $function, $server){
    try{
        log_console(tr('apt function ":function" failed with ":e"', array(':function' => $function.'()', ':e' => $e->getMessage())), 'yellow');

        if($e->getRealCode() == 100){
            /*
             * apt-get failed!
             */
            $data = $e->getData();

            if($data){
                $result = array_pop($data);
                $result = strtolower(trim($result));

                if(str_exists($result, 'dpkg was interrupted, you must manually run \'sudo dpkg --configure -a\' to correct the problem')){
                    log_console(tr('apt reported dpkg was interrupted, trying to fix'), 'yellow');

                    /*
                     * Some previous install failed. Repair and retry
                     */
                    try{
                        apt_fix($server);
                        $function($server);

                    }catch(Exception $f){
                        throw new BException('apt_handle_exception(): apt function ":function" failed, repair failed as well with ":f"', array(':function' => $function.'()', ':f' => $f->getMessage()), $e);
                    }
                }
            }
        }

        throw new BException(tr('apt_handle_exception(): apt function ":function" failed', array(':function' => $function.'()')), $e);

    }catch(Exception $e){
        throw new BException('apt_handle_exception(): Failed', $e);
    }
}
?>

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
        throw new bException('apt_library_init(): Failed', $e);
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
 * @return string The output from the apt-get command
 */
function apt_install($packages, $server = null){
    try{
        return servers_exec($server, 'sudo apt-get -y install "'.str_force($packages, ' ').'"');

    }catch(Exception $e){
        throw new bException('apt_install(): Failed', $e);
    }
}
?>

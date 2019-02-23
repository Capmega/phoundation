<?php
/*
 * Ubuntu library
 *
 * This library contains various functions to manage ubuntu machines
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package linux
 */



/*
 * Install the specified package on the linux operating system
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 * @version 2.4.11: Added function and documentation
 *
 * @param string $package
 * @return void
 */
function ubuntu_install_package($server, $package){
    try{
        load_libs('apt');
        return apt_install($package);

    }catch(Exception $e){
        throw new BException('ubuntu_install_package(): Failed', $e);
    }
}
?>

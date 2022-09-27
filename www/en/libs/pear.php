<?php
/*
 * PEAR library
 *
 * This library is a front-end for the PHP PEAR system
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package pear
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package pear
 *
 * @param
 * @return
 */
function pear_library_init(){
    try{
        ensure_installed(array('name'     => 'pear',
                               'callback' => 'pear_install',
                               'checks'   => array(ROOT.'libs/external/pear/')));

    }catch(Exception $e){
        throw new BException('pear_library_init(): Failed', $e);
    }
}



/*
 * Install the PEAR package
 *
 * NOTE: This function is executed automatically by the pear_library_init() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package pear
 *
 * @param
 * @return
 */
function pear_install($params){
    try{
        safe_exec('sudo apt -y install pear');

    }catch(Exception $e){
        throw new BException('pear_install(): Failed', $e);
    }
}



/*
 * Discover the specified PEAR channel
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package pear
 *
 * @param string $channel The PEAR channel to be discovered
 * @return array the output from the command
 * @throws A BException will be thrown if
 */
function pear_channel_discover($channel){
    try{
        return safe_exec('sudo pear channel-discover '.$channel);

    }catch(Exception $e){
        throw new BException('pear_install(): Failed', $e);
    }
}



/*
 * Install the specified PEAR package
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package pear
 *
 * @param string $channel The PEAR channel to be discovered
 * @return array the output from the "pear install $package" command
 * @throws A BException will be thrown if the PEAR command fails. This exception will contain the output of the failed PEAR command
 */
function pear_install_package($package){
    try{
        return safe_exec('sudo pear install '.$package);

    }catch(Exception $e){
        throw new BException('pear_install(): Failed', $e);
    }
}
?>

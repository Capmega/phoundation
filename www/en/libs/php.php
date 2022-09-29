<?php
/*
 * PHP library
 *
 * This library contains various PHP control functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package php
 */



/*
 * Enable the specified PHP module
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package php
 * @version 2.4.22: Added function and documentation
 *
 * @param string $module The module that has to be enabled
 * @return string The result
 */
function php_enmod($module){
    try{
        safe_exec(array('commands' => array('phpenmod', array('sudo' => true, $module))));

    }catch(Exception $e){
        throw new CoreException('php_enmod(): Failed', $e);
    }
}
?>

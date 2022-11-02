<?php
/*
 * Xapian library
 *
 * This is a front-end library for the xapian search engine
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package xapian
 */

under_construction();

/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package xapian
 * @version 2.2.0: Added function and documentation
 *
 * @return void
 */
function xapian_library_init() {
    try {
        load_config('xapian');

        ensure_installed(array('name'      => 'xapian',
                               'callback'  => 'xapian_install',
                               'checks'    => PATH_ROOT.'libs/external/xapian/xapian.php'));

        include_once(PATH_ROOT.'libs/external/xapian/xapian.php');

    }catch(Exception $e) {
        throw new CoreException('xapian_library_init(): Failed', $e);
    }
}



/*
 * Install the external xapian library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @version 2.2.0: Added function and documentation
 * @package xapian
 *
 * @param
 * @return
 */
function xapian_install($params) {
    try {

    }catch(Exception $e) {
        throw new CoreException('xapian_install(): Failed', $e);
    }
}



/*
 * Create a new Xapian database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package xapian
 * @see xapian_add()
 * @see xapian_query()
 * @version 2.4.0: Added function and documentation
 *
 * @return
 */
function xapian_create() {
    try {

    }catch(Exception $e) {
        throw new CoreException('xapian_create(): Failed', $e);
    }
}



/*
 * Add the specified entry to the specified Xapian database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package xapian
 * @see xapian_create()
 * @version 2.4.0: Added function and documentation
 *
 * @return
 */
function xapian_add() {
    try {

    }catch(Exception $e) {
        throw new CoreException('xapian_add(): Failed', $e);
    }
}



/*
 * Query the specified Xapian database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package xapian
 * @see xapian_create()
 * @version 2.4.0: Added function and documentation
 *
 * @return
 */
function xapian_query() {
    try {

    }catch(Exception $e) {
        throw new CoreException('xapian_query(): Failed', $e);
    }
}



/*
 * Add the specified entry to the specified Xapian database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package xapian
 * @see xapian_create()
 * @version 2.4.0: Added function and documentation
 *
 * @return
 */
function xapian_add() {
    try {

    }catch(Exception $e) {
        throw new CoreException('xapian_add(): Failed', $e);
    }
}



/*
 * Query the specified Xapian database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package xapian
 * @see xapian_create()
 * @version 2.4.0: Added function and documentation
 *
 * @return
 */
function xapian_query() {
    try {

    }catch(Exception $e) {
        throw new CoreException('xapian_query(): Failed', $e);
    }
}



/*
 * Add the specified entry to the specified Xapian database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package xapian
 * @see xapian_create()
 * @version 2.4.0: Added function and documentation
 *
 * @return
 */
function xapian_add() {
    try {

    }catch(Exception $e) {
        throw new CoreException('xapian_add(): Failed', $e);
    }
}



/*
 * Rename the specified Xapian database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package xapian
 * @see xapian_create()
 * @version 2.4.0: Added function and documentation
 *
 * @return
 */
function xapian_rename() {
    try {

    }catch(Exception $e) {
        throw new CoreException('xapian_rename(): Failed', $e);
    }
}
?>

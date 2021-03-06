<?php
/*
 * Slack library
 *
 * This library is a front-end slack extension library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package slack
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
 * @package
 *
 * @return void
 */
function slack_library_init(){
    try{
        ensure_installed(array('name'     => 'slack',
                               'callback' => 'slack_install',
                               'checks'   => array()));

        load_config('slack');

    }catch(Exception $e){
        throw new BException('slack_library_init(): Failed', $e);
    }
}



/*
 * Automatically install dependencies for the slack library
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package slack
 * @see slack_init_library()
 * @version 2.0.3: Added function and documentation
 * @note This function typically gets executed automatically by the slack_init_library() through the ensure_installed() call, and does not need to be run manually
 *
 * @param params $params A parameters array
 * @return void
 */
function slack_install($params){
    try{
        load_libs('composer');
        composer_install('slack-client');

    }catch(Exception $e){
        throw new BException('slack_install(): Failed', $e);
    }
}



/*
 * Send a message to slack
 */
function slack_send(){
    try{

    }catch(Exception $e){
        throw new BException('slack_send(): Failed', $e);
    }
}
?>

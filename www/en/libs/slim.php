<?php
/*
 * PHP Slim library
 *
 * This library contains all required functions to work with PHP slim
 *
 * @url https://getslim.org/
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



slim_init();



/*
 * Load the slim library and its CSS requirements
 */
function slim_init(){
    try{
        ensure_installed(array('name'     => 'slim',
                               'project'  => 'slim',
                               'callback' => 'slim_install',
                               'checks'   => array(ROOT.'vendor/slim')));

    }catch(Exception $e){
        throw new bException('slim_load(): Failed', $e);
    }
}



/*
 * Install the slim library
 */
function slim_install($params){
    try{
        $params['methods'] = array('composer' => array('commands' => ROOT.'scripts/base/composer require "slim/slim"'));
        return install($params);

    }catch(Exception $e){
        throw new bException('slim_install(): Failed', $e);
    }
}
?>

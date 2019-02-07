<?php
/*
 * Minify library
 *
 * This library is a front end for the Minify project
 * @see https://github.com/mrclay/minify
 *
 * Since Base does its own HTML minification online (And JS and CSS
 * minification @deploy time), it will only use the minification
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package minify
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
 * @package empty
 *
 * @return void
 */
function minify_library_init(){
    try{
        ensure_installed(array('name'     => 'minify',
                               'callback' => 'minify_install',
                               'checks'   => array(ROOT.'libs/external/vendor/mrclay/minify')));

    }catch(Exception $e){
        throw new bException('minify_library_init(): Failed', $e);
    }
}



/*
 * Automatically install dependencies for the minifylibrary
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package minify
 * @see minify_init_library()
 * @version 2.0.3: Added function and documentation
 * @note This function typically gets executed automatically by the minify_init_library() through the ensure_installed() call, and does not need to be run manually
 *
 * @param params $params
 * @return void
 */
function minify_install($params){
    try{
        load_libs('composer');
        composer_require('mrclay/minify');

        file_execute_mode(ROOT.'libs/external/', 0770, function(){
            rename(TMP.'/minify/vendor/', ROOT.'libs/external/');
            file_delete(TMP.'/minify/vendor/');
        });

    }catch(Exception $e){
        throw new bException('minify_install(): Failed', $e);
    }
}



/*
 * Return the specified HTML minified
 */
function minify_html($html){
    try{
        include_once(ROOT.'libs/external/vendor/mrclay/minify/lib/Minify/HTML.php');
        include_once(ROOT.'libs/external/vendor/mrclay/minify/lib/Minify/CSS.php');
        include_once(ROOT.'libs/external/vendor/mrclay/jsmin-php/src/JSMin/JSMin.php');
        include_once(ROOT.'libs/external/vendor/mrclay/minify/lib/Minify/CSS/Compressor.php');
        include_once(ROOT.'libs/external/vendor/mrclay/minify/lib/Minify/CommentPreserver.php');

        $html = Minify_HTML::minify($html, array('cssMinifier' => array('Minify_CSS'  , 'minify'),
                                                 'jsMinifier'  => array('\JSMin\JSMin', 'minify')));

// :FIX: This is a temp fix because the minifier appears to use \n as a space?
        $html = str_replace("\n", ' ', $html);

        return $html;

    }catch(Exception $e){
        throw new bException('minify_html(): Failed', $e);
    }
}
?>

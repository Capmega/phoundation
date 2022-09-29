<?php
/*
 * css library
 *
 * This library contains functions to manage CSS
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package purge-css
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
 * @package purge-css
 * @version 2.4.11: Added function and documentation
 *
 * @return void
 */
function css_library_init() {
    try{
        ensure_installed(array('name'     => 'css',
                               'callback' => 'css_setup',
                               'checks'   => ROOT.'node_modules/.bin/purgecss'));

        load_config('css');

    }catch(Exception $e) {
        throw new CoreException('css_library_init(): Failed', $e);
    }
}



/*
 * Setup and install the external css library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @version 2.4.11: Added function and documentation
 * @package purge-css
 *
 * @param
 * @return
 */
function css_setup($params) {
    try{
        ///*
        // * Ensure all targets are clean
        // */
        //file_delete(TMP.'Purge');
        //file_delete(ROOT.'www/'.LANGUAGE.'/libs/vendor/purge-css', false, false, ROOT.'www/'.LANGUAGE.'/libs/vendor');
        //
        ///*
        // * Clone the library
        // */
        //load_libs('git,composer');
        //git_clone('https://github.com/FrancisBaileyH/Purge.git', TMP);
        //
        ///*
        // * Move library to ROOT/libs/vendor directory and ensure its readonly
        // */
        //file_ensure_path(ROOT.'libs/vendor');
        //file_execute_mode(ROOT.'libs/vendor', 0770, function() {
        //    rename(TMP.'Purge', ROOT.'libs/vendor/purge-css');
        //    file_delete(TMP.'Purge');
        //
        //    /*
        //     * Be sure to use version 7 and up of the php-css-parser, since
        //     * version 6 will crash on PHP 7
        //     */
        //    file_replace('"sabberworm/php-css-parser" : "^6.', '"sabberworm/php-css-parser" : "^7.', ROOT.'libs/vendor/purge-css/composer.json');
        //
        //    /*
        //     * Install all depemdancies, make everything readonly
        //     */
        //    composer_install(ROOT.'libs/vendor/purge-css');
        //    file_chmod(ROOT.'libs/vendor/purge-css', 0440, 0550);
        //});

        load_libs('node');
        node_install_npm('purgecss');

    }catch(Exception $e) {
        throw new CoreException('css_setup(): Failed', $e);
    }
}



/*
 * Purge un-used CSS from the specified CSS file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package purge-css
 * @note: This function uses the PHP CSS purge library https://github.com/FrancisBaileyH/Purge.git as backend
 * @version 2.6.19: Added function and documentation
 *
 * @param string $html The HTML file to use
 * @param string $css The CSS file to use
 * @return string The purged CSS file
 */
function css_purge($html, $css) {
    global $_CONFIG, $core;

    try{
        //$purged_css      = 'p-'.$css;
        //$purged_css_file = ROOT.'www/'.LANGUAGE.'/pub/css/'.$purged_css.($_CONFIG['cdn']['min'] ? '.min.css' : '.css');
        //$css_file        = ROOT.'www/'.LANGUAGE.'/pub/css/'.$css       .($_CONFIG['cdn']['min'] ? '.min.css' : '.css');
        //
        //safe_exec(array('commands' => array('cd' , array(ROOT.'libs/vendor/purge-css/src/'),
        //                                    'php', array(ROOT.'libs/vendor/purge-css/src/purge.php', 'purge:run', $css_file, $html, $purged_css_file))));
        //return $purged_css;

        $purged_css      = 'p-'.$css;
        $purged_css_file = ROOT.'www/'.LANGUAGE.'/pub/css/'.$purged_css.($_CONFIG['cdn']['min'] ? '.min.css' : '.css');
        $css_file        = ROOT.'www/'.LANGUAGE.'/pub/css/'.$css       .($_CONFIG['cdn']['min'] ? '.min.css' : '.css');
        $arguments       = array('--css', $css_file, '--content', $html, '--out', TMP);

        /*
         * Ensure that any previous version is deleted
         */
        file_delete($purged_css_file, ROOT.'www/'.LANGUAGE.'/pub/css');

        /*
         * Add list of selectors that should be whitelisted
         */
        if(!empty($_CONFIG['css']['whitelist'][$core->register['script']])) {
            /*
             * Use the whitelist specifically for this page
             */
            $whitelist = &$_CONFIG['css']['whitelist'][$core->register['script']];

        } else {
            /*
             * Use the default whitelist
             */
            $whitelist = &$_CONFIG['css']['whitelist']['default'];
        }

        if($whitelist) {
            $arguments[] = '--whitelist';

            foreach(Arrays::force($whitelist) as $selector) {
                if($selector) {
                    $arguments[] = $selector;
                }
            }
        }

        unset($whitelist);

        /*
         * Purge CSS
         */
        load_libs('node');
        node_exec('./purgecss', $arguments);
        rename(TMP.basename($css_file), $purged_css_file);

        return $purged_css;

    }catch(Exception $e) {
        throw new CoreException('css_purge(): Failed', $e);
    }
}
?>

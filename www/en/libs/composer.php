<?php
/*
 * PHP Composer library
 *
 * This library contains all required functions to work with PHP composer
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package composer
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
 * @package composer
 *
 * @return void
 */
function composer_library_init(){
    try{
        /*
         * Do a version check so we're sure this stuff is supported
         */
        if(version_compare(PHP_VERSION, '5.3.2') < 0){
            throw new BException('composer_library_init(): PHP composer requires PHP 5.3.2+', 'notsupported');
        }

        ensure_installed(array('name'     => 'composer',
                               'callback' => 'composer_setup',
                               'checks'   => array(ROOT.'www/en/libs/external/composer.phar')));

        if(!file_exists(ROOT.'/composer.json')){
            composer_init_file();
        }

        load_config('composer');

    }catch(Exception $e){
        throw new BException('composer_library_init(): Failed', $e);
    }
}



/*
 * Automatically install dependencies for the composer library
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package composer
 * @see composer_library_init()
 * @version 2.0.3: Added function and documentation
 * @note This function typically gets executed automatically by the composer_library_init() through the ensure_installed() call, and does not need to be run manually
 *
 * @param params $params
 * @return void
 */
function composer_setup($params){
    try{
        file_ensure_path(TMP.'composer');

        $file          = download('https://getcomposer.org/installer');
        $file_hash     = hash_file('SHA384', $file);
        $required_hash = download('https://composer.github.io/installer.sig', true);
        $required_hash = trim($required_hash);

        if($file_hash !== $required_hash){
            throw new BException(tr('composer_setup(): File hash check failed for composer-setup.php'), 'hash-fail');
        }

        file_execute_mode(ROOT.'www/en/libs/external/', 0770, function() use ($file) {
            safe_exec(array('commands' => array('php', array($file, '--install-dir', ROOT.'www/en/libs/external/', (VERBOSE ? '' : '--quiet')))));
        });

        file_delete(TMP.'composer');

    }catch(Exception $e){
        throw new BException('composer_setup(): Failed', $e);
    }
}



/*
 *
 */
function composer_init_file(){
    try{
        file_execute_mode(ROOT, 0770, function(){
            file_put_contents(ROOT.'composer.json', "{\n}");
        });

    }catch(Exception $e){
        throw new BException('composer_init_file(): Failed', $e);
    }
}



/*
 * Add the specified package to this project using composer
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package composer
 * @version 2.0.3: Added function and documentation
 * @see composer_install()
 * @example This will install the mrclay/minify package
 * code
 * $result = composer_require('mrclay/minify');
 * showdie($result);
 * /code
 *
 * @param string $package The package to be installed
 * @return void
 */
function composer_require($package, $path = ROOT.'libs'){
    try{
        safe_exec(array('timeout'  => 90,
                        'commands' => array('cd'                              , array($path),
                                            ROOT.'libs/external/composer.phar', array('require', $package))));

    }catch(Exception $e){
        throw new BException('composer_require(): Failed', $e);
    }
}



/*
 * Install the specified package to this project using composer
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package composer
 * @version 2.0.3: Added function and documentation
 * @see composer_require()
 * @example This will install the mrclay/minify package
 * code
 * $result = composer_require('mrclay/minify');
 * showdie($result);
 * /code
 *
 * @param string $package The package to be installed
 * @return void
 */
function composer_install($package){
    try{
        safe_exec(array('commands' => array(ROOT.'libs/external/composer.phar', array('install', $package))));

    }catch(Exception $e){
        throw new BException('composer_install(): Failed', $e);
    }
}
?>

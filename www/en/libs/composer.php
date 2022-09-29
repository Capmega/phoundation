<?php
/*
 * PHP Composer library
 *
 * This library contains all required functions to work with PHP composer
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package composer
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
 * @package composer
 *
 * @return void
 */
function composer_library_init() {
    try{
        ensure_installed(array('name'     => 'composer',
                               'callback' => 'composer_setup',
                               'checks'   => array(ROOT.'www/en/libs/composer.phar')));

        if(!file_exists(ROOT.'/libs/composer.json')) {
            composer_init_file();
        }

        load_config('composer');

    }catch(Exception $e) {
        throw new CoreException('composer_library_init(): Failed', $e);
    }
}



/*
 * Automatically install dependencies for the composer library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function composer_setup($params) {
    try{
        file_ensure_path(TMP.'composer');

        $file          = download('https://getcomposer.org/installer');
        $file_hash     = hash_file('SHA384', $file);
        $required_hash = download('https://composer.github.io/installer.sig', true);
        $required_hash = trim($required_hash);

        if($file_hash !== $required_hash) {
            throw new CoreException(tr('composer_setup(): File hash check failed for composer-setup.php'), 'hash-fail');
        }

        file_execute_mode(ROOT.'www/'.LANGUAGE.'/libs', 0770, function() use ($file) {
            safe_exec(array('commands' => array('php', array($file, '--install-dir', ROOT.'www/en/libs/', (VERBOSE ? '' : '--quiet')))));
        });

        file_delete(TMP.'composer');

    }catch(Exception $e) {
        throw new CoreException('composer_setup(): Failed', $e);
    }
}



/*
 *
 */
function composer_init_file() {
    try{
        if(file_exists(ROOT.'composer.json')) {
            if(!FORCE) {
                throw new CoreException('Composer has already been initialized for this project', 'already-initialized');
            }
        }

        file_execute_mode(ROOT, 0770, function() {
            file_put_contents(ROOT.'www/'.LANGUAGE.'/libs/composer.json', "{\n}");
            chmod(ROOT.'libs/composer.json', 0660);
        });

    }catch(Exception $e) {
        throw new CoreException('composer_init_file(): Failed', $e);
    }
}



/*
 * Execute the specified composer commands
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package composer
 * @version 2.5.179: Added function and documentation
 * @see composer_install()
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
function composer_exec($commands, $path = null) {
    try{
        if(!$commands) {
            throw new CoreException(tr('composer_exec(): No commands specified'), 'not-specified');
        }

        file_execute_mode(ROOT, 0770, function() use ($commands, $path) {
            if($path) {
                file_execute_mode($path, 0770, function() use ($commands, $path) {
                    safe_exec(array('function' => (PLATFORM_CLI ? 'passthru' : 'exec'),
                                    'timeout'  => 30,
                                    'commands' => array('cd'                                      , array($path),
                                                        ROOT.'www/'.LANGUAGE.'/libs/composer.phar', $commands)));
                });

            } else {
                file_execute_mode(ROOT.'www/'.LANGUAGE.'/libs', 0770, function() use ($commands) {
                    file_ensure_path(ROOT.'www/'.LANGUAGE.'/libs/vendor', 0550);

                    file_execute_mode(ROOT.'www/'.LANGUAGE.'/libs/vendor', 0770, function() use ($commands) {
                        file_chmod(array('path'         => ROOT.'www/'.LANGUAGE.'/libs/vendor',
                                         'mode'         => 'ug+w',
                                         'recursive'    => true,
                                         'restrictions' => ROOT.'www/'.LANGUAGE.'/libs/vendor'));

                        safe_exec(array('function' => (PLATFORM_CLI ? 'passthru' : 'exec'),
                                        'timeout'  => 30,
                                        'commands' => array('cd'                                      , array(ROOT.'libs'),
                                                            ROOT.'www/'.LANGUAGE.'/libs/composer.phar', $commands)));

                        file_chmod(array('path'         => ROOT.'www/'.LANGUAGE.'/libs/vendor',
                                         'mode'         => 'ug-w',
                                         'recursive'    => true,
                                         'restrictions' => ROOT.'www/'.LANGUAGE.'/libs/vendor'));
                    });
                });
            }
        });

    }catch(Exception $e) {
        throw new CoreException('composer_exec(): Failed', $e);
    }
}



/*
 * Add the specified package to this project using composer
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package composer
 * @version 2.0.3: Added function and documentation
 * @see composer_exec()
 * @example This will install the mrclay/minify package
 * code
 * $result = composer_require('mrclay/minify');
 * showdie($result);
 * /code
 *
 * @param string $package The package to be installed
 * @return void
 */
function composer_require($packages) {
    try{
        if(!$packages) {
            throw new CoreException(tr('composer_require(): No package specified'), 'not-specified');
        }

        foreach(Arrays::force($packages) as $package) {
            composer_exec(array('require', $package));
        }

    }catch(Exception $e) {
        throw new CoreException('composer_require(): Failed', $e);
    }
}



/*
 * Install the specified package to this project using composer
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package composer
 * @version 2.0.3: Added function and documentation
 * @see composer_exec()
 * @example This will install the mrclay/minify package
 * code
 * $result = composer_require('mrclay/minify');
 * showdie($result);
 * /code
 *
 * @param string $package The package to be installed
 * @return void
 */
function composer_install($path) {
    try{
        if(!$path) {
            throw new CoreException(tr('composer_install(): No path specified'), 'not-specified');
        }

        composer_exec(array('install'), $path);

    }catch(Exception $e) {
        throw new CoreException('composer_install(): Failed', $e);
    }
}
?>

<?php
/*
 * base58 library
 *
 * This library contains base58 functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package base58
 * @dependency extension php-bcmath
 * @dependency stephen-hill base58php.git project
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
 * @package base58
 *
 * @return void
 */
function base58_library_init() {
    try {
        ensure_installed(array('name'      => 'base58',
                               'callback'  => 'base58_install',
                               'checks'    => array(ROOT.'www/en/libs/external/base58php/Base58.php'),
                               'functions' => 'bcadd'));

        load_external(array('base58php/ServiceInterface.php',
                            'base58php/BCMathService.php',
                            'base58php/GMPService.php',
                            'base58php/Base58.php'));

    }catch(Exception $e) {
        throw new CoreException('base58_library_init(): Failed', $e);
    }
}



/*
 * Automatically install dependencies for the base58 library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package base58
 * @see base58_init_library()
 * @version 2.0.3: Added function and documentation
 * @note This function typically gets executed automatically by the base58_library_init() through the ensure_installed() call, and does not need to be run manually
 *
 * @param params $params A parameters array
 * @return void
 */
function base58_install($params) {
    try {
        /*
         * PHP bcmath extension is missing
         */
        load_libs('git,linux');

        File::executeMode(ROOT.'www/'.LANGUAGE.'/libs/external/', 0770, function() {
            $path = git_clone('https://github.com/stephen-hill/base58php.git', TMP, true);
            rename($path, ROOT.'www/'.LANGUAGE.'/libs/external/base58php');
        });

        if (!function_exists('bcadd')) {
            /*
             * PHP bcmath extension is missing
             */
            log_file(tr('PHP bcmath extension missing, installing automatically'), 'base58', 'yellow');
            linux_install_package(null, 'php-bcmath');
        }

    }catch(Exception $e) {
        throw new CoreException('base58_install(): Failed', $e);
    }
}



/*
 * Encode the specified string into a base58 string
 */
function base58_encode($source, $reduced = false) {
    try {
        switch ($reduced) {
            case false:
                $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
                break;

            case 'lower':
                $alphabet = '123456789abcdefghijkmnopqrstuvwxyzabcdefghjklmnpqrstuvwxyz';
                break;

            case 'upper':
                $alphabet = '123456789ABCDEFGHIJKMNOPQRSTUVWXYZABCDEFGHJKLMNPQRSTUVWXYZ';
                break;

            default:
                $alphabet = $reduced;
        }

        $converter = new StephenHill\Base58($alphabet);

		return $converter->encode($source);

    }catch(Exception $e) {
        if ($e->getMessage() == 'Please install the BC Math or GMP extension.') {
            throw new CoreException(tr('base58_encode(): The PHP BC Math or PHP GMP extensions are not installed. On ubuntu, please install or enable these extensions using "sudo apt-get install php-bcmath", "sudo phpenmod bcmath", "sudo apt-get install php-gmp", or "sudo phpenmod gmp"'), 'not-available');
        }

        throw new CoreException(tr('base58_encode(): Failed'), $e);
    }
}



/*
 * Decode the specified base58 string
 */
function base58_decode($base58, $reduced = false) {
    try {
        switch ($reduced) {
            case false:
                $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
                break;

            case 'lower':
                $alphabet = '123456789abcdefghijkmnopqrstuvwxyz';
                break;

            case 'upper':
                $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                break;

            default:
                $alphabet = $reduced;
        }

        $converter = new StephenHill\Base58($alphabet);

		return $converter->decode($source);

    }catch(Exception $e) {
        if ($e->getMessage() == 'Please install the BC Math or GMP extension.') {
            throw new CoreException(tr('base58_decode(): The PHP BC Math or PHP GMP extensions are not installed. On ubuntu, please install or enable these extensions using "sudo apt-get install php-bcmath", "sudo phpenmod bcmath", "sudo apt-get install php-gmp", or "sudo phpenmod gmp"'), 'not-available');
        }

        throw new CoreException(tr('base58_decode(): Failed'), $e);
    }
}
?>

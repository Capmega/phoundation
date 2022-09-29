<?php
/*
 * QR library
 *
 * This library contains functions to encode information in QR images, and decode information from QR images
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 *
 */
function qr_check(){
    try{
        ensure_installed(array('name'      => 'qr',
                               'callback'  => 'qr_install',
                               'checks'    => ROOT.'www/en/libs/external/php-qrcode-decoder/QrReader.php',
                               'functions' => 'gd_info'));

    }catch(Exception $e){
        throw new CoreException('qr_check(): Failed', $e);
    }
}



/*
 * Automatically install dependencies for the qr library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package qr
 * @see qr_init_library()
 * @version 2.0.3: Added function and documentation
 * @note This function typically gets executed automatically by the qr_init_library() through the ensure_installed() call, and does not need to be run manually
 *
 * @param params $params A parameters array
 * @return void
 */
function qr_install(){
    try{
        load_libs('git');

        $path = git_clone('https://github.com/khanamiryan/php-qrcode-detector-decoder.git', TMP, true);

        file_execute_mode(ROOT.'libs/external/', 0770, function(){
            rename($path.'lib', ROOT.'www/'.LANGUAGE.'/libs/external/php-qrcode-decoder');
        });

        file_delete($path);

        if(!is_callable('gd_info')){
            load_libs('php');
            php_enmod('gd');
        }

    }catch(Exception $e){
        throw new CoreException('qr_install(): Failed', $e);
    }
}



/*
 * Encode the specified data in a QR image
 */
function qr_encode($data, $height = 300, $width = 300, $provider = 'google'){
    try{
        switch($provider){
            case 'google':
                load_libs('html');
                return 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl='.urlencode($data).'&choe=UTF-8';

            case 'internal':
under_construction();
                break;

            default:
                throw new CoreException(tr('qr_decode(): Unknown provider ":provider" specified', array(':provider' => $provider)), 'unknown');
        }

    }catch(Exception $e){
        throw new CoreException('qr_encode(): Failed', $e);
    }
}



/*
 * Encode the specified data in a QR image
 */
function qr_decode($image){
    try{
        qr_check();
        load_external('php-qrcode-decoder/QrReader.php');

        $qrcode = new QrReader($image);
        $text   = $qrcode->text();

        return $text;

    }catch(Exception $e){
        throw new CoreException('qr_decode(): Failed', $e);
    }
}
?>

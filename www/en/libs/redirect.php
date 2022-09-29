<?php
/*
 * Redirect library
 *
 * This library can redirect
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Do a redirect for the specified code
 */
function redirect_from_code($code){
    try{
        $url = sql_get('SELECT `url` FROM `redirects` WHERE `code` = :code', 'url', array(':code' => $code));

        if(!$url){
            throw new CoreException(tr('redirect_from_code(): Specified code ":code" does not exist', array(':code' => $code)), 'not-exists');
        }

        redirect($url);

    }catch(Exception $e){
        throw new CoreException('redirect_from_code(): Failed', $e);
    }
}



/*
 * Add a code and the URL it should redirect to
 */
function redirect_add_code($url, $code = null){
    try{
        if(!$code){
            $code = uniqid('', true);
        }

        sql_query('INSERT INTO `redirects` (`createdby`, `ip`, `code`, `url`)
                   VALUES                  (:createdby , :ip , :code , :url )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':ip'        => $_SERVER['REMOTE_ADDR'],
                         ':code'      => $code,
                         ':url'       => $url));

       return $code;

    }catch(Exception $e){
        throw new CoreException('redirect_add_code(): Failed', $e);
    }
}
?>

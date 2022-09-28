<?php

/**
 * Class Webserver
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 <copyright@capmega.com>
 * @package Phoundation\Core
 */
class Webserver
{
    /**
     * Disconnect from webserver but let the process continue working
     */
    function disconnect(): void
    {
        switch (php_sapi_name()) {
            case 'fpm-fcgi':
                fastcgi_finish_request();
                break;

            case '':
                throw new OutOfBoundsException(tr('disconnect(): No SAPI detected'), 'unknown');

            default:
                throw new OutOfBoundsException(tr('disconnect(): Unknown SAPI ":sapi" detected', array(':sapi' => php_sapi_name())), 'unknown');
        }
    }
}


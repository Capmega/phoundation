<?php

declare(strict_types=1);

/**
 * Class Webserver
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
                throw new OutOfBoundsException(tr('No SAPI detected'));

            default:
                throw new OutOfBoundsException(tr('Unknown SAPI ":sapi" detected', [':sapi' => php_sapi_name()]));
        }
    }
}


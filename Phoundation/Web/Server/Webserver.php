<?php

/**
 * Class Webserver
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Server;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Server\Interfaces\WebserverInterface;


class Webserver implements WebserverInterface
{
    /**
     * @param string|null $environment
     *
     * @return WebserverInterface
     */
    public static function getServerObject(?string $environment): WebserverInterface
    {
        $server = static::detectServer($environment);

        switch ($server) {
            case 'apache':
                return new Apache();

            case 'nginx':
                return new Nginx();

            case 'litespeed':
                return new Litespeed();

            default:
                throw new OutOfBoundsException(tr('Unsupported webserver ":server" detected', [
                    'server' => $server
                ]));
        }
    }


    /**
     * Detects what webserver should be used for this, or the specified environment
     *
     * @param string|null $environment
     *
     * @todo implement. Will right now only return "apache"
     * @return string
     */
    public static function detectServer(?string $environment): string
    {
        return 'apache';
    }


    /**
     * Disconnect from webserver but let the process continue working
     */
    function disconnect(): void
    {
        switch (php_sapi_name()) {
            case 'fpm-fcgi':
                fastcgi_finish_request();
                break;

            case 'phpdbg':
            case 'embed':
            case 'apache2handler':
            case 'cgi-fcgi':
            case 'cli-server':
            case 'litespeed':
            case 'cli':
                throw new OutOfBoundsException(tr('Unsupported SAPI ":sapi" encountered', [
                    'sapi' => php_sapi_name()
                ]));

            case false:
                throw new OutOfBoundsException(tr('No SAPI detected'));

            default:
                throw new OutOfBoundsException(tr('Unknown SAPI ":sapi" detected', [
                    ':sapi' => php_sapi_name()
                ]));
        }
    }
}


<?php

declare(strict_types=1);

namespace Phoundation\Core\Sessions;


/**
 * Class GetVariables
 *
 * Manage HTTP GET variables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class GetVariables extends HttpVariables
{
    /**
     * Encode the GET variables
     *
     * @return void
     */
    public static function encode(): void
    {
        global $_GET;
        static::encodeVariables($_GET);
    }


    /**
     * Decode the HTTP variables
     *
     * @return void
     */
    public static function decode(): void
    {
        global $_GET;
        static::decodeVariables($_GET);
    }
}

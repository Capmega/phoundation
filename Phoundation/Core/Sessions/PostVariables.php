<?php

/**
 * Class GetVariables
 *
 * Manage HTTP POST variables
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */

declare(strict_types=1);

namespace Phoundation\Core\Sessions;


class PostVariables extends HttpVariables
{
    /**
     * Encode the GET variables
     *
     * @return void
     */
    public static function encode(): void
    {
        global $_POST;
        static::encodeVariables($_POST);
    }


    /**
     * Decode the HTTP variables
     *
     * @return void
     */
    public static function decode(): void
    {
        global $_POST;
        static::decodeVariables($_POST);
    }
}

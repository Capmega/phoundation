<?php

use Phoundation\Core\Config;



/**
 * Class Paging
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Paging
{
    /**
     *
     *
     * @param int|null $limit
     * @return int
     */
    public static function limit(?int $limit = null): int
    {
        if ($limit) {
            return $limit;
        }

        return Config::get('data.paging.default_limit');
    }



    /**
     *
     *
     * @param int|null $page
     * @return int
     */
    public static function page(?int $page): int
    {

    }
}
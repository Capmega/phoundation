<?php

namespace Phoundation\Databases\Sql;

use Phoundation\Core\Config;

/**
 * Class Paging
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Paging
{
    /**
     * Returns the paging limit for this session
     *
     * @param int|null $limit
     * @return int
     */
    public static function getLimit(?int $limit = null): int
    {
        if ($limit) {
            return $limit;
        }

        return Config::getInteger('data.paging.default-limit', 50);
    }


    /**
     * Returns the current page that we're at
     *
     * @param int|null $page
     * @return int
     */
    public static function getPage(?int $page): int
    {
        return $page || 1;
    }
}
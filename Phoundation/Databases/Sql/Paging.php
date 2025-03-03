<?php

/**
 * Class Paging
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql;



use Phoundation\Web\Requests\Request;

class Paging
{
    /**
     * Returns the autosuggest paging limit for this session
     *
     * @param int|null $limit
     *
     * @return int
     */
    public static function getAutosuggestLimit(?int $limit = 20): int
    {
        return static::getLimit($limit);
    }


    /**
     * Returns the paging limit for this session
     *
     * @param int|null $limit
     *
     * @return int
     */
    public static function getLimit(?int $limit = null): int
    {
        if ($limit) {
            return $limit;
        }

        if (PLATFORM_CLI) {
            // Return CLI limits
            return config()->getInteger('data.paging.limit.cli', 50);
        }

        // Return limits for the web request type
        return config()->getInteger('data.paging.limit.web.' . Request::getRequestType()->value, 50);
    }


    /**
     * Returns the current page that we're at
     *
     * @param int|null $page
     *
     * @return int
     */
    public static function getPage(?int $page): int
    {
        return $page ?? 1;
    }
}

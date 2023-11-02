<?php

namespace Phoundation\Databases\Sql;

use Phoundation\Core\Config;


/**
 * Class Limit
 *
 * Contains various methods to manage SQL result limits
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Limit
{
    /**
     * Returns the default limit for shell auto completion
     *
     * @param int $limit
     * @return int
     */
    static function shellAutoCompletion(int $limit = 50): int
    {
        return Config::getInteger('shell.autocomplete.limit', $limit);
    }
}
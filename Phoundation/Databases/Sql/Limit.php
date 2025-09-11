<?php

/**
 * Class Limit
 *
 * Contains various methods to manage SQL result limits
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql;


class Limit
{
    /**
     * Returns the default limit for shell auto-completion
     *
     * @param int $limit
     *
     * @return int
     */
    static function getShellAutoCompletion(int $limit = 100_000): int
    {
        return config()->getInteger('autocomplete.shell.limit', $limit);
    }


    /**
     * Returns the default limit for web auto-completion
     *
     * @param int $limit
     *
     * @return int
     */
    static function getWebAutosuggest(int $limit = 100): int
    {
        return config()->getInteger('autocomplete.web.ajax.limit', $limit);
    }
}

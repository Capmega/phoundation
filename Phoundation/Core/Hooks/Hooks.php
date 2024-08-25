<?php

/**
 * Hooks class
 *
 *
 *
 * @see       DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Hooks;

use Phoundation\Core\Libraries\Libraries;
use Phoundation\Data\DataEntry\DataIterator;


class Hooks extends DataIterator
{
    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'core_hooks';
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataTypes(): ?string
    {
        return Hook::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'seo_name';
    }


    /**
     * Instructs the Libraries class to clear the commands cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Libraries::clearWebCache();
    }


    /**
     * Instructs the Libraries class to have each library rebuild its command cache
     *
     * @return void
     */
    public static function rebuildCache(): void
    {
        Libraries::rebuildHookCache();
    }
}

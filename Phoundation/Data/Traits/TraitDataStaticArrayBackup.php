<?php

/**
 * Trait TraitDataStaticArrayBackup
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataStaticArrayBackup
{
    /**
     * Internal backup array, containing the original values
     *
     * @var array|null $_backup
     */
    protected static ?array $_backup = null;


    /**
     * Returns the backup data
     *
     * @return array|null
     */
    public static function getBackup(): ?array
    {
        return static::$_backup;
    }
}

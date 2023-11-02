<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Exception\DataEntryReadonlyException;


/**
 * Trait DataStaticReadonly
 *
 * This adds static readonly state registration to objects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataStaticReadonly
{
    /**
     * Registers if this object is readonly or not
     *
     * @var bool $readonly
     */
    protected static bool $readonly = false;


    /**
     * Throws an exception for the given action if the object is readonly
     *
     * @param string $action
     * @return void
     * @throws DataEntryReadonlyException
     */
    public static function checkReadonly(string $action): void
    {
        if (static::$readonly) {
            throw new DataEntryReadonlyException(tr('Unable to perform action ":action", the object is readonly', [
                ':action' => $action,
            ]));
        }
    }


    /**
     * Returns if this object is readonly or not
     *
     * @return bool
     */
    public static function getReadonly(): bool
    {
        return static::$readonly;
    }


    /**
     * Sets if this object is readonly or not
     *
     * @param bool $readonly
     * @return void
     */
    public static function setReadonly(bool $readonly): void
    {
        static::$readonly = $readonly;
    }
}

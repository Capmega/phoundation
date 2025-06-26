<?php

/**
 * Trait TraitDataReadonly
 *
 * This adds readonly state registration to objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntries\Exception\DataEntryReadonlyException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Utils\Strings;


trait TraitDataReadonly
{
    /**
     * Registers if this object is readonly or not
     *
     * @var bool $readonly
     */
    protected bool $readonly = false;


    /**
     * Throws an exception for the given action if the object is readonly
     *
     * @param string $action
     *
     * @return static
     * @throws DataEntryReadonlyException
     */
    public function checkReadonly(string $action): static
    {
        if ($this->readonly) {
            throw new DataEntryReadonlyException(tr('Unable to perform action ":action", the ":object" object is readonly', [
                ':action' => $action,
                ':object' => Strings::fromReverse(static::class, '\\'),
            ]));
        }

        if (($this instanceof DataEntryInterface) and $this->isReadonly()) {
            if ($this->sourceLoadedFromConfiguration()) {
                throw new DataEntryReadonlyException(tr('Unable to perform action ":action", the ":object" object is readonly because it was read from configuration', [
                    ':action' => $action,
                    ':object' => Strings::fromReverse(static::class, '\\'),
                ]));
            }

            throw new DataEntryReadonlyException(tr('Unable to perform action ":action", the ":object" object is readonly', [
                ':action' => $action,
                ':object' => Strings::fromReverse(static::class, '\\'),
            ]));
        }

        return $this;
    }


    /**
     * Returns if this object is readonly or not
     *
     * @return bool
     */
    public function isReadonly(): bool
    {
        return $this->readonly;
    }


    /**
     * Returns if this object is readonly or not
     *
     * @return bool
     */
    public function getReadonly(): bool
    {
        return $this->readonly;
    }


    /**
     * Sets if this object is readonly or not
     *
     * @param bool $readonly
     *
     * @return static
     */
    public function setReadonly(bool $readonly): static
    {
        $this->readonly = $readonly;
        return $this;
    }
}

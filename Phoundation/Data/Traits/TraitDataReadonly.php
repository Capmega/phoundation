<?php

/**
 * Trait TraitDataReadonly
 *
 * This adds readonly state registration to objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Exception\DataEntryReadonlyException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
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
                ':object' => Strings::fromReverse(get_class($this), '\\'),
            ]));
        }
        if (($this instanceof DataEntryInterface) and $this->isReadonly()) {
            if ($this->isConfigured()) {
                throw new DataEntryReadonlyException(tr('Unable to perform action ":action", the ":object" object is readonly because it was read from configuration', [
                    ':action' => $action,
                    ':object' => Strings::fromReverse(get_class($this), '\\'),
                ]));
            }
            throw new DataEntryReadonlyException(tr('Unable to perform action ":action", the ":object" object is readonly', [
                ':action' => $action,
                ':object' => Strings::fromReverse(get_class($this), '\\'),
            ]));
        }

        return $this;
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

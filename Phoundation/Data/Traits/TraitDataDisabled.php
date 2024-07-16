<?php

/**
 * Trait TraitDataDisabled
 *
 * This adds disabled state registration to objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Exception\DataEntryDisabledException;
use Phoundation\Utils\Strings;

trait TraitDataDisabled
{
    /**
     * Registers if this object is disabled or not
     *
     * @var bool $disabled
     */
    protected bool $disabled = false;


    /**
     * Throws an exception for the given action if the object is disabled
     *
     * @param string $action
     *
     * @return static
     * @throws DataEntryDisabledException
     */
    public function checkDisabled(string $action): static
    {
        if ($this->disabled) {
            throw new DataEntryDisabledException(tr('Unable to perform action ":action", the ":object" object is disabled', [
                ':action' => $action,
                ':object' => Strings::fromReverse(get_class($this), '\\'),
            ]));
        }

        return $this;
    }


    /**
     * Returns if this object is disabled or not
     *
     * @return bool
     */
    public function getDisabled(): bool
    {
        return $this->disabled;
    }


    /**
     * Sets if this object is disabled or not
     *
     * @param bool $disabled
     *
     * @return static
     */
    public function setDisabled(bool $disabled): static
    {
        $this->disabled = $disabled;

        return $this;
    }
}

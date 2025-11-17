<?php

/**
 * Trait TraitDataDisabled
 *
 * This adds disabled state registration to objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntries\Exception\DataEntryDisabledException;
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
                ':object' => Strings::fromReverse(static::class, '\\'),
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
     * Returns if this object is disabled or not
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }


    /**
     * Sets if this object is disabled or not
     *
     * @param bool              $disabled
     * @param bool|null         $set_readonly
     * @param string|false|null $title
     *
     * @return static
     */
    public function setDisabled(bool $disabled, ?bool $set_readonly = null, string|false|null $title = false): static
    {
        // TODO This is godawful, get a better way of doing this. At least check the interface or something? This is just a quick stopgap until I have time for a better solution
        if (method_exists($this, 'setTitle')) {
            $this->setTitle($title);
        }

        $this->disabled = $disabled;

        if ($set_readonly) {
            return $this->setReadonly($disabled, false);
        }

        return $this;
    }
}

<?php

/**
 * Trait TraitDataParent
 *
 * This trait adds support for storing DataEntryInterface $o_parent
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Exception\OutOfBoundsException;
use ReturnTypeWillChange;


trait TraitDataParent
{
    /**
     * Tracks the parent object
     *
     * @var DataEntryInterface|null $o_parent
     */
    protected ?DataEntryInterface $o_parent = null;

    /**
     * Tracks if this object requires a parent, or not
     *
     * @var bool $require_parent
     */
    protected bool $require_parent = false;


    /**
     * Returns the DataEntryInterface parent object
     *
     * @return DataEntryInterface
     */
    #[ReturnTypeWillChange] public function getParentObject(): DataEntryInterface
    {
        return $this->o_parent;
    }


    /**
     * Sets the DataEntryInterface parent object
     *
     * @param DataEntryInterface $o_parent
     *
     * @return static
     */
    public function setParentObject(DataEntryInterface $o_parent): static
    {
        $this->o_parent = $o_parent;
        return $this;
    }


    /**
     * Will throw an OutOfBoundsException exception if no parent was set for this list
     *
     * @param string $action
     *
     * @return static
     */
    protected function checkParent(string $action): static
    {
        if (!$this->o_parent and $this->require_parent) {
            throw new OutOfBoundsException(tr('Cannot ":action", no parent specified for this ":class" object', [
                ':action' => $action,
                ':class'  => static::class,
            ]));
        }

        return $this;
    }


    /**
     * Returns if this Iterator requires a parent or not
     *
     * @return bool
     */
    public function getRequireParent(): bool
    {
        return $this->require_parent;
    }


    /**
     * Sets if this Iterator requires a parent or not
     *
     * @param bool $require_parent
     *
     * @return static
     */
    public function setRequireParent(bool $require_parent): static
    {
        $this->require_parent = $require_parent;
        return $this;
    }
}

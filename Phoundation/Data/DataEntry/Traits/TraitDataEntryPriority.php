<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;

/**
 * Trait TraitDataEntryPriority
 *
 * This trait contains methods for DataEntry objects that require a priority
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryPriority
{
    /**
     * Returns the priority for this object
     *
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->getValueTypesafe('int', 'priority', 50);
    }


    /**
     * Sets the priority for this object
     *
     * @param int|null $priority
     *
     * @return static
     */
    public function setPriority(?int $priority): static
    {
        if (is_numeric($priority) and (($priority < 0) or ($priority > 100))) {
            throw new OutOfBoundsException(tr('Specified priority ":priority" is invalid, it should be a number from 0 to 100', [
                ':priority' => $priority,
            ]));
        }

        return $this->set($priority, 'priority');
    }
}

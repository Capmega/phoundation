<?php

/**
 * Trait TraitDataEntrySecond
 *
 * This trait contains methods for DataEntry objects that require a second
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntrySecond
{
    /**
     * Returns the second for this object
     *
     * @return int|null
     */
    public function getSecond(): ?int
    {
        return $this->getTypesafe('int', 'second');
    }


    /**
     * Sets the second for this object
     *
     * @param int|null $second
     *
     * @return static
     */
    public function setSecond(?int $second): static
    {
        return $this->set(get_null($second), 'second');
    }
}

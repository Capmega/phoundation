<?php

/**
 * Trait TraitDataEntryTitle
 *
 * This trait contains methods for DataEntry objects that require a title
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

trait TraitDataEntryTitle
{
    /**
     * Returns the title for this object
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getValueTypesafe('string', 'title');
    }


    /**
     * Sets the title for this object
     *
     * @param string|null $title
     *
     * @return static
     */
    public function setTitle(?string $title): static
    {
        return $this->set($title, 'title');
    }
}

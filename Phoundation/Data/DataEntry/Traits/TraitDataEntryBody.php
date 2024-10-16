<?php

/**
 * Trait TraitDataEntryBody
 *
 * This trait contains methods for DataEntry objects that require a body
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryBody
{
    /**
     * Returns the body for this object
     *
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->getTypesafe('string', 'body');
    }


    /**
     * Sets the body for this object
     *
     * @param string|null $body
     *
     * @return static
     */
    public function setBody(?string $body): static
    {
        return $this->set($body, 'body');
    }
}

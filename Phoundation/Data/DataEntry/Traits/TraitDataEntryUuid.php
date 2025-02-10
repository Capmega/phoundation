<?php

/**
 * Trait TraitDataEntryUuid
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Utils\Strings;
use Stringable;


trait TraitDataEntryUuid
{
    /**
     * Returns the uuid for this object
     *
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->getTypesafe('string', 'uuid');
    }


    /**
     * Sets the uuid for this object
     *
     * @param Stringable|string|null $uuid
     *
     * @return static
     */
    public function setUuid(Stringable|string|null $uuid): static
    {
        return $this->set(get_null((string) $uuid), 'uuid');
    }


    /**
     * Generates a uuid for this object
     *
     * @param Stringable|string|null $data
     *
     * @return static
     */
    public function generateUuid(Stringable|string|null $data = null): static
    {
        return $this->setUuid(Strings::getUuid($data));
    }
}

<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Utils\Strings;
use Stringable;


/**
 * Trait TraitDataEntryUuid
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataEntryUuid
{
    /**
     * Returns the uuid for this object
     *
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->getValueTypesafe('string', 'uuid');
    }


    /**
     * Sets the uuid for this object
     *
     * @param Stringable|string|null $uuid
     * @return static
     */
    public function setUuid(Stringable|string|null $uuid): static
    {
        return $this->setValue('uuid', (string) $uuid);
    }


    /**
     * Generates a uuid for this object
     *
     * @param Stringable|string|null $data
     * @return static
     */
    public function generateUuid(Stringable|string|null $data = null): static
    {
        return $this->setValue('uuid', Strings::getUuid($data));
    }
}
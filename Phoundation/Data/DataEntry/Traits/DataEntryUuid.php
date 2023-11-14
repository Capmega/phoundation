<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Strings;
use Stringable;


/**
 * Trait DataEntryUuid
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryUuid
{
    /**
     * Returns the uuid for this object
     *
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->getSourceFieldValue('string', 'uuid');
    }


    /**
     * Sets the uuid for this object
     *
     * @param Stringable|string|null $uuid
     * @return static
     */
    public function setUuid(Stringable|string|null $uuid): static
    {
        return $this->setSourceValue('uuid', (string) $uuid);
    }


    /**
     * Generates a uuid for this object
     *
     * @param Stringable|string|null $data
     * @return static
     */
    public function generateUuid(Stringable|string|null $data = null): static
    {
        return $this->setSourceValue('uuid', Strings::generateUuid($data));
    }
}

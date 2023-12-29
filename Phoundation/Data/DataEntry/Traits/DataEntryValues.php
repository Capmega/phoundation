<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryValues
 *
 * This trait contains methods for DataEntry objects that require values
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryValues
{
    /**
     * Returns the values for this object
     *
     * @return array|null
     */
    public function getValues(): ?array
    {
        return $this->getSourceFieldValue('array', 'values');
    }


    /**
     * Sets the values for this object
     *
     * @param array|null $values
     * @return static
     */
    public function setValues(?array $values): static
    {
        return $this->setSourceValue('values', $values);
    }
}

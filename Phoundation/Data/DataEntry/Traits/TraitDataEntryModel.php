<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Stringable;


/**
 * Trait TraitDataEntryModel
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataEntryModel
{
    /**
     * Returns the model for this object
     *
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'model');
    }


    /**
     * Sets the model for this object
     *
     * @param Stringable|string|null $model
     * @return static
     */
    public function setModel(Stringable|string|null $model): static
    {
        return $this->setSourceValue('model', (string) $model);
    }
}

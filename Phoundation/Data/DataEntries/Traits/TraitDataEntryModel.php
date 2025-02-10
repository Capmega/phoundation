<?php

/**
 * Trait TraitDataEntryModel
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Stringable;


trait TraitDataEntryModel
{
    /**
     * Returns the model for this object
     *
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->getTypesafe('string', 'model');
    }


    /**
     * Sets the model for this object
     *
     * @param Stringable|string|null $model
     *
     * @return static
     */
    public function setModel(Stringable|string|null $model): static
    {
        return $this->set(get_null((string) $model), 'model');
    }
}

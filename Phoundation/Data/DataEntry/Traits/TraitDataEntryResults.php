<?php

/**
 * Trait TraitDataEntryResults
 *
 * This trait contains methods for DataEntry objects that require a results
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryResults
{
    /**
     * Returns the results for this object
     *
     * @return string|null
     */
    public function getResults(): ?string
    {
        return $this->getTypesafe('string', 'results');
    }


    /**
     * Sets the results for this object
     *
     * @param string|null $results
     *
     * @return static
     */
    public function setResults(?string $results): static
    {
        return $this->set($results, 'results');
    }
}

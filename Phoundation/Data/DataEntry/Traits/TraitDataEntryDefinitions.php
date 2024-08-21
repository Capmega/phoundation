<?php

/**
 * Trait TraitDataEntryDefinitions
 *
 * This trait contains methods for the data definitions of DataEntry or DataIterator objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;


trait TraitDataEntryDefinitions
{
    /**
     * Meta-information about the keys in this DataEntry
     *
     * @var DefinitionsInterface|null $definitions
     */
    protected ?DefinitionsInterface $definitions = null;


    /**
     * Returns the definitions for the fields in this table
     *
     * @return DefinitionsInterface|null
     */
    public function getDefinitionsObject(): ?DefinitionsInterface
    {
        return $this->definitions;
    }
}

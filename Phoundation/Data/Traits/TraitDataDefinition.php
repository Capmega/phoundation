<?php

/**
 * Trait TraitDataDefinition
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;

trait TraitDataDefinition
{
    /**
     * The definition to use
     *
     * @var DefinitionInterface|null $definition
     */
    protected ?DefinitionInterface $definition = null;


    /**
     * Returns the definition
     *
     * @return DefinitionInterface|null
     */
    public function getDefinition(): ?DefinitionInterface
    {
        return $this->definition;
    }


    /**
     * Sets the definition
     *
     * @param DefinitionInterface|null $definition
     *
     * @return static
     */
    public function setDefinition(DefinitionInterface|null $definition): static
    {
        $this->definition = $definition;

        return $this;
    }
}

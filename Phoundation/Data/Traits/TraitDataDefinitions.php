<?php

/**
 * Trait TraitDataDefinitions
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentable.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;


trait TraitDataDefinitions
{
    /**
     * The DataEntry / DataForm definitions object
     *
     * @var DefinitionsInterface|null $definitions
     */
    protected ?DefinitionsInterface $definitions = null;


    /**
     * Returns the DataEntry / DataForm definitions object
     *
     * @return DefinitionsInterface|null
     */
    public function getDefinitionsObject(): ?DefinitionsInterface
    {
        return $this->definitions;
    }


    /**
     * Sets the DataEntry / DataForm definitions object
     *
     * @param DefinitionsInterface $definitions
     *
     * @return static
     */
    public function setDefinitionsObject(DefinitionsInterface $definitions): static
    {
        $this->definitions = $definitions;

        return $this;
    }
}

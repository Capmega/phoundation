<?php

/**
 * Trait TraitDataDefinitions
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentable.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;


trait TraitDataDefinitions
{
    /**
     * The DataEntry / DataForm definitions object
     *
     * @var DefinitionsInterface|null $o_definitions
     */
    protected ?DefinitionsInterface $o_definitions = null;


    /**
     * Returns the DataEntry / DataForm definitions object
     *
     * @return DefinitionsInterface|null
     */
    public function getDefinitionsObject(): ?DefinitionsInterface
    {
        return $this->o_definitions;
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
        $this->o_definitions = $definitions;
        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Phoundation\Storage;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;


/**
 * Class Page
 *
 * This class is a page in the Phoundation storage system
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Storage
 */
class Page extends DataEntry
{
    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions;
    }
}
<?php

/**
 * Trait TraitDataEntryDefinitions
 *
 * This trait contains methods for the data definitions of DataEntry or DataIterator objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryNotInitializedException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Exception\OutOfBoundsException;


trait TraitDataDefinitions
{
    /**
     * Meta-information about the keys in this DataEntry
     *
     * @var DefinitionsInterface|null $o_definitions
     */
    protected ?DefinitionsInterface $o_definitions = null;


    /**
     * Returns the definitions for the fields in this table
     *
     * @return DefinitionsInterface|null
     */
    public function getDefinitionsObject(): ?DefinitionsInterface
    {
        return $this->checkDefinitionsObject();
    }


    /**
     * Checks and returns definitions if this DataEntry class has any definitions at all available
     *
     * If no definitions are available, an OutOfBoundsException exception will be thrown
     *
     * If the object is a DataEntry class object, and has not yet been initialized, it will initialize automatically
     *
     * @return DefinitionsInterface
     * @throws OutOfBoundsException | DataEntryNotInitializedException
     */
    protected function checkDefinitionsObject(): DefinitionsInterface
    {
        if (empty($this->definitions)) {
            if ($this instanceof DataEntryInterface) {
                if ($this->isInitialized()) {
                    throw new OutOfBoundsException(tr('The ":class" DataEntry object is initialized but has no Definitions class object set', [
                        ':class' => get_class($this),
                    ]));
                }

                // The object has not yet been initialized, do so now.
                $this->initialize(false);

            } else {
                throw OutOfBoundsException::new(tr('The ":class" object has no Definitions object set', [
                    ':class' => get_class($this),
                ]));
            }
        }

        if ($this->definitions->isEmpty()) {
            throw new OutOfBoundsException(tr('The ":class" DataEntry object has a Definitions object but it is empty', [
                ':class' => get_class($this),
            ]));
        }

        return $this->definitions;
    }
}

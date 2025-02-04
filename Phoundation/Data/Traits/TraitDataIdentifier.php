<?php

/**
 * Trait TraitDataIdentifier
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opendebug.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Exception\DataEntryException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\IdentifierInterface;
use Phoundation\Data\Interfaces\IteratorInterface;


trait TraitDataIdentifier
{
    /**
     * Tracks if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @var array|null $identifier
     */
    protected array|null $identifier = null;


    /**
     * Returns if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @return array|null
     */
    public function getIdentifier(): array|null
    {
        return $this->identifier;
    }


    /**
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     *
     * @return static
     */
    public function setIdentifier(IdentifierInterface|array|string|int|null $identifier): static
    {
        if ($this->isNew()) {
            // Ensure $identifier is either NULL or a key => value array
            if ($identifier instanceof DataEntryInterface) {
                $identifier = $identifier->getIdentifier();

            } elseif ($identifier instanceof IteratorInterface) {
                $identifier =  $identifier->getSource();

            } elseif (is_numeric($identifier)) {
                $identifier = [static::getIdColumn() => $identifier];

            } elseif (is_string($identifier)) {
                $identifier = [static::getUniqueColumn() => $identifier];
            }

            // Set the identifier
            $this->identifier = $identifier;
            return $this;
        }

        // This DataEntry object already contains data from a source, we cannot set the identifier anymore
        throw DataEntryException::new(tr('Cannot set identifier ":identifier" for DataEntry class ":class", the object already contains source data', [
            ':class'      => $this::class,
            ':identifier' => $identifier,
        ]))->setData([
            'class'      => $this::class,
            'identifier' => $identifier,
            'source'     => $this->source
        ]);
    }
}

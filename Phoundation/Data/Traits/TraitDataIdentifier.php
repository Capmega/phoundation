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

use Phoundation\Data\DataEntries\Exception\DataEntryDoubleIdentifierSpecifiedException;
use Phoundation\Data\DataEntries\Exception\DataEntryException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Security\Incidents\Incident;


trait TraitDataIdentifier
{
    /**
     * Tracks a DataEntry type identifier
     *
     * @var array|false|null $identifier
     */
    protected array|false|null $identifier = false;


    /**
     * Returns a DataEntry type identifier
     *
     * array: Contains a list of column => value entries
     * null: No identifier
     * false: No identifier has been specified yet
     *
     * @return array|false|null
     */
    public function getIdentifier(): array|false|null
    {
        return $this->identifier;
    }


    /**
     * Returns a normalized array $identifier from all possible identifier types
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return array
     */
    public static function normalizeIdentifier(IdentifierInterface|array|string|int|false|null $identifier): array
    {
        // Ensure $identifier is either NULL or a key => value array
        if ($identifier instanceof DataEntryInterface) {
            return $identifier->getIdentifier();
        }

        if ($identifier instanceof DataIteratorInterface) {
            return $identifier->getSource();
        }

        if (is_numeric($identifier)) {
            return [static::getIdColumn() => $identifier];
        }

        if (is_string($identifier)) {
            return [static::getUniqueColumn() => $identifier];

        }

        return get_null($identifier);
    }


    /**
     * Sets a DataEntry type identifier
     *
     * Valid identifier values can be one of the following:
     *
     * IdentifierInterface: Another DataEntry, or DataIterator (which will be converted to array internally)
     * array: Contains a list of column => value entries
     * string: Will be converted to a single static::getUniqueColumn() => value
     * integer: Will be converted to a single static::getIdColumn() => value
     * null: No identifier
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static
     */
    public function setIdentifier(IdentifierInterface|array|string|int|false|null $identifier): static
    {
        if ($identifier) {
            if ($this->identifier) {
                Incident::new(DataEntryDoubleIdentifierSpecifiedException::new(tr('Cannot set identifier ":new" for DataEntry object of class ":class", it already has identifier ":identifier" specified', [
                    ':new'        => $identifier,
                    ':identifier' => $this->identifier,
                    ':class'      => $this::class,
                ]))->setData([
                    'new'        => $identifier,
                    'identifier' => $this->identifier,
                    ':class'      => $this::class,
                ]))->save()->throw();
            }

            if ($this->isNotNew()) {
                // This DataEntry object already contains data from a source, it can't set the identifier anymore
                throw DataEntryException::new(tr('Cannot set identifier ":identifier" for DataEntry class ":class", the object already contains source data', [
                    ':class'      => $this::class,
                    ':identifier' => $identifier,
                ]))->setData([
                    'class'      => $this::class,
                    'identifier' => $identifier,
                    'source'     => $this->source
                ]);
            }

            // Set the identifier
            $this->identifier = static::normalizeIdentifier($identifier);
        }

        $this->cache_key = null;
        return $this;
    }
}

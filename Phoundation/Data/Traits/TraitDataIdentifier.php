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
                show('GODVERDOMME!');
                $e = new DataEntryDoubleIdentifierSpecifiedException(tr('Cannot set identifier ":new" for DataEntry object of class ":class", it already has identifier ":identifier" specified', [
                    ':new'        => $identifier,
                    ':identifier' => $this->identifier,
                    ':class'      => $this::class,
                ]));

                Incident::new($e)->save()->throw();
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

            // Ensure $identifier is either NULL or a key => value array
            if ($identifier instanceof DataEntryInterface) {
                $identifier = $identifier->getIdentifier();

            } elseif ($identifier instanceof DataIteratorInterface) {
                $identifier =  $identifier->getSource();

            } elseif (is_numeric($identifier)) {
                $identifier = [static::getIdColumn() => $identifier];

            } elseif (is_string($identifier)) {
                $identifier = [static::getUniqueColumn() => $identifier];

            } else {
                $identifier = get_null($identifier);
            }

            // Set the identifier
            $this->cache_key  = null;
            $this->identifier = $identifier;
        }

        return $this;
    }
}

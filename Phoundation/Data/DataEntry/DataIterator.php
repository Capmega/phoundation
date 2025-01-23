<?php

/**
 * Class DataIterator
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;

class DataIterator extends DataIteratorCore
{
    /**
     * DataIterator class constructor
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        if ($source) {
            $this->setSource($source);
        }

        // Set what datatypes this DataIterator will accept
        // If this data iterator had a source specified, consider it loaded
        $this->setAcceptedDataTypes(static::getDefaultContentDataType())
             ->is_loaded = (bool) $source;
    }


    /**
     * Returns a new DataIterator type object
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     *
     * @return static
     */
    public static function new(IteratorInterface|array|string|PDOStatement|null $source = null): static
    {
        return new static($source);
    }
}

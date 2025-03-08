<?php

/**
 * Class DataIterator
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;


class DataIterator extends DataIteratorCore
{
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

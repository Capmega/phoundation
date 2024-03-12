<?php

declare(strict_types=1);

namespace Phoundation\Data;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;


/**
 * Class Iterator
 *
 * This is a slightly extended interface to the default PHP iterator class. This class also requires the following
 * methods:
 *
 * - Iterator::getCount() Returns the number of elements contained in this object
 *
 * - Iterator::getFirst() Returns the first element contained in this object without changing the internal pointer
 *
 * - Iterator::getLast() Returns the last element contained in this object without changing the internal pointer
 *
 * - Iterator::clear() Clears all the internal content for this object
 *
 * - Iterator::delete() Deletes the specified key
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Iterator extends IteratorCore
{
    /**
     * Iterator class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        if ($source) {
            $this->setSource($source);

        } else {
            $this->source = [];
        }
    }


    /**
     * Returns a new static object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @return static
     */
    public static function new(IteratorInterface|PDOStatement|array|string|null $source = null): static
    {
        return new static($source);
    }
}

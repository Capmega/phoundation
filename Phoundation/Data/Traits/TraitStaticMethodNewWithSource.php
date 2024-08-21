<?php

/**
 * Trait TraitStaticMethodNewWithSource
 *
 * This trait contains just the static new() command without any parameters
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;


trait TraitStaticMethodNewWithSource
{
    /**
     * NewSource class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        if ($source) {
            $this->setSource($source);
        }
    }


    /**
     * Returns a new static object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     *
     * @return static
     */
    public static function new(IteratorInterface|PDOStatement|array|string|null $source = null): static
    {
        return new static($source);
    }
}

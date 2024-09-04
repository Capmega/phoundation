<?php

/**
 * Trait TraitDataIteratorSource
 *
 *
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
use Phoundation\Data\Iterator;


trait TraitDataIteratorSource
{
    /**
     * @var IteratorInterface|null
     */
    protected ?IteratorInterface $source = null;


    /**
     * Returns the iterator source
     *
     * @return IteratorInterface|null
     */
    public function getSource(): ?IteratorInterface
    {
        return $this->source;
    }


    /**
     * Sets the iterator source
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
    {
        if ($source) {
            $this->source = new Iterator($source);

        } else {
            $this->source = null;
        }

        return $this;
    }
}

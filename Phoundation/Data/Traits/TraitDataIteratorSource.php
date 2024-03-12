<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;


/**
 * Trait TraitDataIteratorSource
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
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
    public function getIteratorSource(): ?IteratorInterface
    {
        return $this->source;
    }


    /**
     * Sets the iterator source
     *
     * @param IteratorInterface|null $source
     * @return static
     */
    public function setIteratorSource(?IteratorInterface $source): static
    {
        $this->source = $source;
        return $this;
    }
}
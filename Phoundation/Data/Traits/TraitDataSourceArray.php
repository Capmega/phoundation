<?php

/**
 * Trait TraitDataSourceArray
 *
 * This trait contains the basic methods required to use a source array
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
use Phoundation\Utils\Arrays;


trait TraitDataSourceArray
{
    /**
     * The source to use
     *
     * @var array $source
     */
    protected array $source = [];


    /**
     * Returns the source data when cast to array
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->getSource();
    }


    /**
     * Returns the source
     *
     * @return array
     */
    public function getSource(): array
    {
        return $this->source;
    }


    /**
     * Returns a list of all internal definition keys
     *
     * @return mixed
     */
    public function getSourceKeys(): array
    {
        return array_keys($this->source);
    }


    /**
     * Sets the source
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null
     * @param array|null                                       $execute
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
    {
        $this->source = Arrays::extractSourceArray($source, $execute);

        return $this;
    }


    /**
     * Returns the number of items contained in this object
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->source);
    }


    /**
     * Returns the number of items contained in this object
     *
     * Wrapper for IteratorCore::getCount()
     *
     * @return int
     */
    public function count(): int
    {
        return $this->getCount();
    }
}

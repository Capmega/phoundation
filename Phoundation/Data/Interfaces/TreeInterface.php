<?php

/**
 * Trait DataTree
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

use PDOStatement;

interface TreeInterface extends IteratorInterface
{
    /**
     * Returns the source of this tree as JSON data
     *
     * @return string
     */
    public function getJson(): string;


    /**
     * Sets the source of this tree from the specified JSON data
     *
     * @return static
     */
    public function setJson(?string $json): static;


    /**
     * @inheritDoc
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static;


    /**
     * Returns the source in tree-view format
     *
     * @return array
     */
    public function getTreeViewSource(): array;
}

<?php

 namespace Phoundation\Web\Html\Components\Widgets\Interfaces;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;

interface TreeViewerInterface
{
    /**
     * Sets the internal source directly
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     * @param bool                                             $filter_meta
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null, bool $filter_meta = false): static;

    /**
     * @inheritDoc
     */
    public function render(): ?string;
}

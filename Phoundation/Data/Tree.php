<?php

namespace Phoundation\Data;

use PDOStatement;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Interfaces\TreeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;


/**
 * Trait DataTree
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Tree extends Iterator implements TreeInterface
{
    /**
     * Tree class constructor
     *
     * @param ArrayableInterface|array|null $source
     */
    public function __construct(ArrayableInterface|array|null $source = null)
    {
        parent::__construct($source);
    }


    /**
     * Returns the source in tree-view format
     *
     * @return array
     */
    public function getTreeViewSource(): array
    {
        return $this->formatSourceToTreeView($this->source);
    }


    /**
     * Returns the source of this tree as JSON data
     *
     * @param bool $tree_view_format
     * @return string
     */
    public function getJson(bool $tree_view_format = false): string
    {
        if ($tree_view_format) {
            return Json::encode($this->getTreeViewSource());
        }

        return Json::encode($this->source);
    }


    /**
     * Sets the source of this tree from the specified JSON data
     *
     * @return $this
     */
    public function setJson(?string $json): static
    {
        $json = get_null($json);
        $tree = Json::decode($json);

        if (!is_array($tree)) {
            if (!($tree instanceof TreeInterface)) {
                throw OutOfBoundsException::new(tr('Cannot use specified data as source for tree, it is not tree data'))
                    ->setData(['tree' => $tree]);
            }

            $tree = $tree->getSource();
        }

        $this->source = $tree;
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
    {
        $this->checkSourceDataType($source);
        return parent::setSource($source, $execute);
    }


    /**
     * Checks the source data that its either an array, PDOstatement, string, NULL, or a TreeInterface
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @return void
     */
    protected function checkSourceDataType(IteratorInterface|PDOStatement|array|string|null $source = null): void
    {
        if ($source instanceof IteratorInterface) {
            if (!$source instanceof TreeInterface) {
                throw OutOfBoundsException::new(tr('Cannot set specified source for this tree object, it is an IteratorInterface instead of a TreeInterface'))
                    ->setData(['source' => $source]);
            }
        }

    }


    /**
     * Returns the specified source as a tree-view source
     *
     * @param array $source
     * @return array
     */
    protected function formatSourceToTreeView(array $source): array
    {
        $return = [];

        foreach ($source as $key => $value) {
            if ($value instanceof TreeInterface){
                $value = $value->getSource();
            }

            if (is_array($value)) {
                $entry['name']     = $key;
                $entry['children'] = $this->formatSourceToTreeView($value);

            } else {
                $entry['name'] = $value;
            }

            $return[] = $entry;
        }

        return $return;
    }
}
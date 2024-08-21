<?php

/**
 * Trait TraitDataTree
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

use Phoundation\Data\Interfaces\TreeInterface;
use Phoundation\Data\Tree;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;


trait TraitDataTree
{
    /**
     * The tree for this object
     *
     * @var TreeInterface|null $tree
     */
    protected ?TreeInterface $tree = null;


    /**
     * Returns the tree data
     *
     * @return TreeInterface|null
     */
    public function getTree(): ?TreeInterface
    {
        return $this->tree;
    }


    /**
     * Sets the tree data
     *
     * @param TreeInterface|array|string|null $tree
     *
     * @return static
     */
    public function setTree(TreeInterface|array|string|null $tree): static
    {
        $tree = get_null($tree);
        if (is_string($tree)) {
            $tree = Json::decode($tree);
            if (!is_array($tree)) {
                if (!($tree instanceof TreeInterface)) {
                    throw OutOfBoundsException::new(tr('Cannot use specified data as source for tree, it is not tree data'))
                                              ->setData(['tree' => $tree]);
                }
            }
        }
        $this->tree = new Tree($tree);

        return $this;
    }
}

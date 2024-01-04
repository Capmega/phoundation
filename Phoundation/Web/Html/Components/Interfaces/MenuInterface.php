<?php

namespace Phoundation\Web\Html\Components\Interfaces;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Menu;


/**
 * Interface MenuInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
interface MenuInterface
{
    /**
     * Set the menu source and ensure all URL's are absolute
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     * @return $this
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static;

    /**
     * Append the specified menu to the end of this menu
     *
     * @param Menu|array $menu
     * @return $this
     */
    public function appendMenu(Menu|array $menu): static;

    /**
     * Append the specified menu to the beginning of this menu
     *
     * @param Menu|array $menu
     * @return $this
     */
    public function prependMenu(Menu|array $menu): static;
}
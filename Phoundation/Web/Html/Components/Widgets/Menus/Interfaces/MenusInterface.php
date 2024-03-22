<?php

/**
 * Menus class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */

namespace Phoundation\Web\Html\Components\Widgets\Menus\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;

interface MenusInterface extends IteratorInterface
{
    /**
     * Returns the specified menu
     *
     * @param string $menu
     * @return MenuInterface|null
     */
    public function getMenu(string $menu): ?MenuInterface;

    /**
     * Returns the primary menu
     *
     * @return MenuInterface|null
     */
    public function getPrimaryMenu(): ?MenuInterface;

    /**
     * Sets the primary menu
     *
     * @param MenuInterface|null $menu
     * @return static
     */
    public function setPrimaryMenu(?MenuInterface $menu): static;

    /**
     * Returns the secondary menu
     *
     * @return MenuInterface|null
     */
    public function getSecondaryMenu(): ?MenuInterface;

    /**
     * Sets the secondary menu
     *
     * @param MenuInterface|null $menu
     * @return static
     */
    public function setSecondaryMenu(?MenuInterface $menu): static;

    /**
     * Set multiple menus
     *
     * @note This will clear all already defined menus
     * @param array $menus
     * @return static
     */
    public function setMenus(array $menus): static;

    /**
     * Add multiple menus
     *
     * @param array $menus
     * @return static
     */
    public function addMenus(array $menus): static;

    /**
     * Add a menu
     *
     * @param string $name
     * @param MenuInterface|null $menu
     * @return static
     */
    public function addMenu(string $name, MenuInterface|null $menu): static;

    /**
     * Load the menu contents from the database
     *
     * @param bool $clear
     * @param bool $only_if_empty
     * @return static
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static;
}

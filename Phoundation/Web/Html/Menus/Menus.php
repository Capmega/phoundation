<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Menus;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Html\Components\Interfaces\MenuInterface;
use Phoundation\Web\Html\Components\Menu;


/**
 * Menus class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
class Menus extends Iterator implements IteratorInterface
{
    /**
     * @var array $menus
     */
    protected array $menus;


    /**
     * Returns the specified menu
     *
     * @param string $menu
     * @return MenuInterface|null
     */
    public function getMenu(string $menu): ?MenuInterface
    {
        return isset_get($this->menus[$menu], Menu::new());
    }


    /**
     * Returns the primary menu
     *
     * @return MenuInterface|null
     */
    public function getPrimaryMenu(): ?MenuInterface
    {
        return $this->getMenu('primary');
    }


    /**
     * Sets the primary menu
     *
     * @param MenuInterface|null $menu
     * @return static
     */
    public function setPrimaryMenu(?MenuInterface $menu): static
    {
        $this->menus['primary'] = $menu;
        return $this;
    }


    /**
     * Returns the secondary menu
     *
     * @return MenuInterface|null
     */
    public function getSecondaryMenu(): ?MenuInterface
    {
        return $this->getMenu('secondary');
    }


    /**
     * Sets the secondary menu
     *
     * @param MenuInterface|null $menu
     * @return static
     */
    public function setSecondaryMenu(?MenuInterface $menu): static
    {
        $this->menus['secondary'] = $menu;
        return $this;
    }


    /**
     * Set multiple menus
     *
     * @note This will clear all already defined menus
     * @param array $menus
     * @return static
     */
    public function setMenus(array $menus): static
    {
        $this->menus = [];
        return $this->addMenus($menus);
    }


    /**
     * Add multiple menus
     *
     * @param array $menus
     * @return static
     */
    public function addMenus(array $menus): static
    {
        foreach ($menus as $key => $value) {
            $this->addMenu($key, $value);
        }

        return $this;
    }


    /**
     * Add a menu
     *
     * @param string $name
     * @param MenuInterface|null $menu
     * @return static
     */
    public function addMenu(string $name, MenuInterface|null $menu): static
    {
        if ($menu !== null) {
            $this->menus[$name] = $menu;
        }

        return $this;
    }


    /**
     * Load the menu contents from the database
     *
     * @param bool $clear
     * @return static
     */
    public function load(bool $clear = true): static
    {
        throw new UnderConstructionException();
//        if (Core::stateIs('setup')) {
//            // In setup mode we don't need menus...
//            $this->primary_menu   = Menu::new();
//            $this->secondary_menu = Menu::new();
//            return;
//        }
//
//        $primary_menu   = sql()->getColumn('SELECT `value` FROM `key_value_store` WHERE `key` = :key', [':key' => 'primary_menu']);
//        $secondary_menu = sql()->getColumn('SELECT `value` FROM `key_value_store` WHERE `key` = :key', [':key' => 'secondary_menu']);
//
//        if ($primary_menu) {
//            $this->primary_menu = Menu::new($primary_menu);
//        } else {
//            $this->primary_menu = $this->menus->getPrimaryMenu();
//        }
//
//        if ($secondary_menu) {
//            $this->secondary_menu = Menu::new($secondary_menu);
//        } else {
//            $this->secondary_menu = $this->menus->getSecondaryMenu();
//        }
    }
}
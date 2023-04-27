<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Menus;

use Iterator;
use Phoundation\Core\Core;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Http\Html\Components\Menu;


/**
 * Menus class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
class Menus implements Iterator
{
    /**
     * @var array $menus
     */
    protected array $menus;


    /**
     * Returns the specified menu
     *
     * @param string $menu
     * @return Menu|null
     */
    public function getMenu(string $menu): ?Menu
    {
        return isset_get($this->menus[$menu], Menu::new());
    }


    /**
     * Returns the primary menu
     *
     * @return Menu|null
     */
    public function getPrimaryMenu(): ?Menu
    {
        return $this->getMenu('primary');
    }


    /**
     * Sets the primary menu
     *
     * @param Menu|null $menu
     * @return static
     */
    public function setPrimaryMenu(?Menu $menu): static
    {
        $this->menus['primary'] = $menu;
        return $this;
    }


    /**
     * Returns the secondary menu
     *
     * @return Menu|null
     */
    public function getSecondaryMenu(): ?Menu
    {
        return $this->getMenu('secondary');
    }


    /**
     * Sets the secondary menu
     *
     * @param Menu|null $menu
     * @return static
     */
    public function setSecondaryMenu(?Menu $menu): static
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
     * @param Menu|null $menu
     * @return static
     */
    public function addMenu(string $name, Menu|null $menu): static
    {
        if ($menu !== null) {
            $this->menus[$name] = $menu;
        }

        return $this;
    }


    /**
     * Returns the amount of menus available
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->menus);
    }


    /**
     * Clears the menus from memory
     *
     * @return static
     */
    public function clear(): static
    {
        $this->menus = [];
        return $this;
    }


    /**
     * Load the menu contents from database
     *
     * @return static
     */
    public function load(): static
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


    /**
     * Returns the current menu
     *
     * @return Menu
     */
    public function current(): Menu
    {
        return current($this->menus);
    }


    /**
     * Progresses the internal pointer to the next menu
     *
     * @return void
     */
    public function next(): void
    {
        next($this->menus);
    }


    /**
     * Returns the current key for the current menu
     *
     * @return string
     */
    public function key(): string
    {
        return key($this->menus);
    }


    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->menus[key($this->menus)]);
    }


    /**
     * Rewinds the internal pointer
     *
     * @return void
     */
    public function rewind(): void
    {
        reset($this->menus);
    }
}
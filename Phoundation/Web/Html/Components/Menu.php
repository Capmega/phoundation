<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use PDOStatement;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Interfaces\MenuInterface;
use Phoundation\Web\Http\UrlBuilder;


/**
 * Menu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
class Menu extends ElementsBlock implements MenuInterface
{
    /**
     * Set the menu source and ensure all URL's are absolute
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     * @return $this
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
    {
        $source = $this->makeUrlsAbsolute($source);
        return parent::setSource($source);
    }


    /**
     * Recursively make all "url" keys absolute URLs, recursing into "menu" keys
     */
    protected function makeUrlsAbsolute(array $source): array
    {
        // Ensure all URL's are absolute
        foreach ($source as $label => &$entry) {
            if (is_string($entry)) {
                $entry = ['url' => $entry];
            }

            if (array_key_exists('rights', $entry)) {
                if (!Session::getUser()->hasAllRights($entry['rights'])) {
                    // User doesn't have access
                    unset($source[$label]);
                    continue;
                }
            }

            if (array_key_exists('url', $entry)) {
                $entry['url'] = UrlBuilder::getWww($entry['url']);
            }

            if (array_key_exists('menu', $entry)) {
                // Recurse
                $entry['menu'] = $this->makeUrlsAbsolute($entry['menu']);

                if (!$entry['menu']) {
                    // The entire submenu is empty, remove this empty top entry too
                    unset($source[$label]);
                }
            }
        }

        unset($entry);
        return $source;
    }


    /**
     * Append the specified menu to the end of this menu
     *
     * @param MenuInterface|array $menu
     * @return $this
     */
    public function appendMenu(MenuInterface|array $menu): static
    {
        if (is_object($menu)) {
            $menu = $menu->__toArray();
        }

        $this->source = array_merge($this->source, $menu);
        return $this;
    }


    /**
     * Append the specified menu to the beginning of this menu
     *
     * @param MenuInterface|array $menu
     * @return $this
     */
    public function prependMenu(MenuInterface|array $menu): static
    {
        if (is_object($menu)) {
            $menu = $menu->__toArray();
        }

        $this->source = array_merge($menu, $this->source);
        return $this;
    }
}

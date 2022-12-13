<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Web\Http\Html\Elements\ElementsBlock;



/**
 * AdminLte Plugin SideBar class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class SideBar extends ElementsBlock
{
    /**
     * A list of items that will be displayed in the top menu bar in the specified order
     *
     * @var array $items
     */
    protected array $items = [];

    /**
     * The site logo
     *
     * @var string|null $logo
     */
    protected ?string $logo = null;

    /**
     * The site menu
     *
     * @var array|null
     */
    protected ?array $menu = null;

    /**
     * The profile image block
     *
     * @var ProfileImage|null
     */
    protected ?ProfileImage $profile = null;

    /**
     * The profile menu
     *
     * @var array|null
     */
    protected ?array $profile_menu = null;

    /**
     * The modal for the signin page
     *
     * @var Modal|null $sign_in_modal
     */
    protected ?Modal $sign_in_modal = null;



    /**
     * Sets the navbar menu
     *
     * @param array|null $menu
     * @return static
     */
    public function setMenu(?array $menu): static
    {
        $this->menu = $menu;
        return $this;
    }



    /**
     * Returns the navbar menu
     *
     * @return array|null
     */
    public function getMenu(): ?array
    {
        return $this->menu;
    }



    /**
     * Sets the navbar profile menu
     *
     * @param array|null $menu
     * @return static
     */
    public function setProfileMenu(?array $menu): static
    {
        $this->profile_menu = $menu;
        return $this;
    }



    /**
     * Returns the navbar profile menu
     *
     * @return array|null
     */
    public function getProfileMenu(): ?array
    {
        return $this->profile_menu;
    }



    /**
     * Returns the navbar sign-in modal
     *
     * @return Modal|null
     */
    public function getSignInModal(): ?Modal
    {
        return $this->sign_in_modal;
    }



    /**
     * Sets the navbar signin modal
     *
     * @param Modal|null $sign_in_modal
     * @return static
     */
    public function setSignInModal(?Modal $sign_in_modal): static
    {
        $this->sign_in_modal = $sign_in_modal;
        return $this;
    }



    /**
     * Renders and returns the sidebar
     *
     * @return string|null
     */
    abstract public function render(): ?string;



    /**
     * Renders the HTML for the sidebar menu
     *
     * @param array $menu
     * @param bool $sub_menu
     * @return string
     */
    abstract protected function renderMenu(array $menu, bool $sub_menu = false): string;
}
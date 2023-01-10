<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Content\Images\Image;



/**
 * Phoundation Panel class
 *
 * This standard webinterface class contains the basic functionalities to render web panels
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Panel extends ElementsBlock
{
    /**
     * A list of items that will be displayed in the panel in the specified order
     *
     * @var array $items
     */
    protected array $items;

    /**
     * The logo for the panel
     *
     * @var Image $logo
     */
    protected Image $logo;

    /**
     * Menu for this panel
     *
     * @var Menu|null $menu
     */
    protected ?Menu $menu = null;

    /**
     * The profile image block
     *
     * @var ImageMenu
     */
    protected ImageMenu $profile_image;

    /**
     * Modals for this panel
     *
     * @var Modals $modals
     */
    protected Modals $modals;



    /**
     * Sets the panel menu
     *
     * @param Menu|null $menu
     * @return static
     */
    public function setMenu(?Menu $menu): static
    {
        $this->menu = $menu;
        return $this;
    }



    /**
     * Returns the panel menu
     *
     * @return Menu|null
     */
    public function getMenu(): ?Menu
    {
        return $this->menu;
    }



    /**
     * Returns the panel profile image
     *
     * @return ProfileImage|null
     */
    public function getProfileImage(): ?ProfileImage
    {
        if (isset($this->profile_image)) {
            return $this->profile_image;
        }

        return null;
    }



    /**
     * Sets the panel profile image
     *
     * @param ImageMenu $profile_image
     * @return static
     */
    public function setProfileImage(ImageMenu $profile_image): static
    {
        $this->profile_image = $profile_image;
        return $this;
    }



    /**
     * Returns the panel logo
     *
     * @return string|null
     */
    public function getLogo(): ?string
    {
        if (isset($this->logo)) {
            return $this->logo;
        }

        return null;
    }



    /**
     * Sets the panel profile image
     *
     * @param Image|string $logo
     * @return static
     */
    public function setLogo(Image|string $logo): static
    {
        if (is_string($logo)) {
            $logo = Image::new($logo);
        }

        $this->logo = $logo;
        return $this;
    }



    /**
     * Returns the panel modals
     *
     * @return Modals
     */
    public function getModals(): Modals
    {
        if (!isset($this->modals)) {
            $this->modals = new Modals();
        }

        return $this->modals;
    }



    /**
     * Returns the panel profile image
     *
     * @return array
     */
    public function getItems(): array
    {
        if (!isset($this->items)) {
            return [];
        }

        return $this->items;
    }



    /**
     * Sets the panel items
     *
     * @param array $items
     * @return static
     */
    public function setItems(array $items): static
    {
        $this->items = [];
        $this->addItems($items);
        return $this;
    }



    /**
     * Adds the specified items to the panel
     *
     * @param array $items
     * @return static
     */
    public function addItems(array $items): static
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }



    /**
     * Adds an item to the panel
     *
     * @param string $item
     * @return static
     */
    public function addItem(string $item): static
    {
        if (!isset($this->items)) {
            $this->items = [];
        }

        $this->items[] = $item;
        return $this;
    }
}
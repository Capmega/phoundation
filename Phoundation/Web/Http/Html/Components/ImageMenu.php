<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Content\Images\UsesImage;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Elements\ElementsBlock;



/**
 * ImageMenu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class ImageMenu extends ElementsBlock
{
    use UsesImage;



    /**
     * The menu items [label => url]
     *
     * @var array|null $menu
     */
    protected ?array $menu = null;

    /**
     * The image URL, if no menu is available
     *
     * @var string|null $url
     */
    protected ?string $url = null;

    /**
     * The image modal selector
     *
     * @var string|null $modal_selector
     */
    protected ?string $modal_selector = null;



    /**
     * ImageMenu class constructor
     */
    public function __construct()
    {
        if ($this->height === null) {
            $this->height = 25;
        }

        parent::__construct();
    }



    /**
     * Sets the menu items
     *
     * @param array|null $menu
     * @return static
     */
    public function setMenu(?array $menu): static
    {
        if ($menu and $this->url) {
            throw new OutOfBoundsException(tr('Cannot set menu for image menu, the image URL has already been configured'));
        }

        $this->menu = $menu;
        return $this;
    }



    /**
     * Returns the menu items
     *
     * @return array|null
     */
    public function getMenu(): ?array
    {
        return $this->menu;
    }



    /**
     * Sets the URL
     *
     * @param string|null $url
     * @return static
     */
    public function setUrl(?string $url): static
    {
        if ($url and $this->menu) {
            throw new OutOfBoundsException(tr('Cannot set URL for image menu, the menu has already been configured'));
        }

        $this->url = $url;
        return $this;
    }



    /**
     * Returns the modal selector
     *
     * @return string|null
     */
    public function getModalSelector(): ?string
    {
        return $this->modal_selector;
    }



    /**
     * Sets the modal selector
     *
     * @param string|null $modal_selector
     * @return static
     */
    public function setModalSelector(?string $modal_selector): static
    {
        if ($modal_selector and $this->menu) {
            throw new OutOfBoundsException(tr('Cannot set modal for image menu, the menu has already been configured'));
        }

        $this->modal_selector = $modal_selector;
        return $this;
    }



    /**
     * Returns the URL
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }



    /**
     * Renders and returns the image menu block HTML
     *
     * @return string|null
     */
    public abstract function render(): ?string;
}
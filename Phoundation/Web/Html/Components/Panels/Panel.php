<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Panels;

use Phoundation\Content\Images\Image;
use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\ImageMenu;
use Phoundation\Web\Html\Components\Interfaces\MenuInterface;
use Phoundation\Web\Html\Components\Modals\Modals;
use Phoundation\Web\Html\Components\Mode;
use Phoundation\Web\Html\Components\Panels\Interfaces\PanelInterface;
use Phoundation\Web\Html\Components\ProfileImage;


/**
 * Phoundation Panel class
 *
 * This standard webinterface class contains the basic functionalities to render web panels
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Panel extends ElementsBlock implements PanelInterface
{
    use Mode;


    /**
     * Modals for this panel
     *
     * @var Modals $modals
     */
    protected Modals $modals;


    /**
     * Sets the panel menu
     *
     * @param MenuInterface|null $menu
     * @return static
     */
    public function setMenu(?MenuInterface $menu): static
    {
        $this->source['menu'] = $menu;
        return $this;
    }


    /**
     * Returns the panel menu
     *
     * @return MenuInterface|null
     */
    public function getMenu(): ?MenuInterface
    {
        return isset_get($this->source['menu']);
    }


    /**
     * Returns the panel profile image
     *
     * @return ProfileImage|null
     */
    public function getProfileImage(): ?ProfileImage
    {
        return isset_get($this->source['profile_image']);
    }


    /**
     * Sets the panel profile image
     *
     * @param ImageMenu $profile_image
     * @return static
     */
    public function setProfileImage(ImageMenu $profile_image): static
    {
        $this->source['profile_image'] = $profile_image;
        return $this;
    }


    /**
     * Returns the panel logo
     *
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return isset_get($this->source['logo']);
    }


    /**
     * Sets the panel profile image
     *
     * @param ImageInterface|string $logo
     * @return static
     */
    public function setLogo(ImageInterface|string $logo): static
    {
        if (is_string($logo)) {
            $logo = Image::new($logo);
        }

        $this->source['logo'] = $logo;
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
}
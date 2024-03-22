<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Panels;

use PDOStatement;
use Phoundation\Content\Images\Image;
use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Web\Html\Components\Avatar;
use Phoundation\Web\Html\Components\Buttons\Button;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Icons\Icon;
use Phoundation\Web\Html\Components\Logo;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\ImageMenu;
use Phoundation\Web\Html\Components\Widgets\LanguagesDropDown;
use Phoundation\Web\Html\Components\Widgets\Menus\Interfaces\MenuInterface;
use Phoundation\Web\Html\Components\Widgets\Menus\Menu;
use Phoundation\Web\Html\Components\Widgets\MessagesDropDown;
use Phoundation\Web\Html\Components\Widgets\Modals\Modals;
use Phoundation\Web\Html\Components\Widgets\NotificationsDropDown;
use Phoundation\Web\Html\Components\Widgets\Panels\Interfaces\PanelInterface;
use Phoundation\Web\Html\Components\Widgets\ProfileImage;
use Phoundation\Web\Html\Enums\EnumBootstrapColor;
use Phoundation\Web\Html\Traits\TraitMode;
use Phoundation\Web\Html\Traits\TraitBootstrapColor;


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
    use TraitMode;
    use TraitBootstrapColor;


    /**
     * Tracks the elements in this panel
     *
     * @var IteratorInterface
     */
    protected IteratorInterface $elements;

    /**
     * The top notifications drop down
     *
     * @var NotificationsDropDown $notifications
     */
    protected NotificationsDropDown $notifications;

    /**
     * The top messages drop down
     *
     * @var MessagesDropDown $messages
     */
    protected MessagesDropDown $messages;

    /**
     * The top languages drop down
     *
     * @var LanguagesDropDown $languages
     */
    protected LanguagesDropDown $languages;

    /**
     *
     *
     * @var IteratorInterface $logos
     */
    protected IteratorInterface $logos;

    /**
     *
     *
     * @var IteratorInterface $menus
     */
    protected IteratorInterface $menus;

    /**
     *
     *
     * @var IteratorInterface $buttons
     */
    protected IteratorInterface $buttons;

    /**
     *
     *
     * @var IteratorInterface $breadcrumbs
     */
    protected IteratorInterface $breadcrumbs;

    /**
     *
     *
     * @var IteratorInterface $texts
     */
    protected IteratorInterface $texts;

    /**
     *
     *
     * @var IteratorInterface $avatars
     */
    protected IteratorInterface $avatars;

    /**
     *
     *
     * @var IteratorInterface $icons
     */
    protected IteratorInterface $icons;

    /**
     * Modals for this panel
     *
     * @var Modals $modals
     */
    protected Modals $modals;


    /**
     * Returns the logos iterator
     *
     * @return IteratorInterface
     */
    public function getLogos(): IteratorInterface
    {
        if (empty($this->logos)) {
            $this->logos = Iterator::new()->setDataTypes('object:' . Logo::class);
        }

        return $this->logos;
    }


    /**
     * Returns the menus iterator
     *
     * @return IteratorInterface
     */
    public function getMenusObject(): IteratorInterface
    {
        if (empty($this->menus)) {
            $this->menus = Iterator::new()->setDataTypes('object:' . Menu::class);
        }

        return $this->menus;
    }


    /**
     * Returns the buttons iterator
     *
     * @return IteratorInterface
     */
    public function getButtons(): IteratorInterface
    {
        if (empty($this->buttons)) {
            $this->buttons = Iterator::new()->setDataTypes('object:' . Button::class);
        }

        return $this->buttons;
    }


    /**
     * Returns the breadcrumbs iterator
     *
     * @return IteratorInterface
     */
    public function getBreadcrumbs(): IteratorInterface
    {
        if (empty($this->breadcrumbs)) {
            $this->breadcrumbs = Iterator::new()->setDataTypes('object:' . BreadCrumbs::class);
        }

        return $this->breadcrumbs;
    }


    /**
     * Returns the texts iterator
     *
     * @return IteratorInterface
     */
    public function getTexts(): IteratorInterface
    {
        if (empty($this->texts)) {
            $this->texts = Iterator::new()->setDataTypes('string');
        }

        return $this->texts;
    }


    /**
     * Returns the avatars iterator
     *
     * @return IteratorInterface
     */
    public function getAvatars(): IteratorInterface
    {
        if (empty($this->avatars)) {
            $this->avatars = Iterator::new()->setDataTypes('object:' . Avatar::class);
        }

        return $this->avatars;
    }


    /**
     * Returns the icons iterator
     *
     * @return IteratorInterface
     */
    public function getIcons(): IteratorInterface
    {
        if (empty($this->icons)) {
            $this->icons = Iterator::new()->setDataTypes('object:' . Icon::class);
        }

        return $this->icons;
    }


    /**
     * Panel class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $content
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $content = null)
    {
        parent::__construct($content);
        $this->background_color = EnumBootstrapColor::primary;
    }


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


    /**
     * Access to the elements object
     *
     * @return IteratorInterface
     */
    public function getElementsObject(): IteratorInterface
    {
        return $this->elements;
    }


    /**
     * Access to the elements object
     *
     * @param IteratorInterface|array $elements
     * @return TopPanel
     */
    public function setElementsObject(IteratorInterface|array $elements): static
    {
        $this->elements = new Iterator($elements);
        return $this;
    }


    /**
     * Returns the notifications drop-down object
     *
     * @return NotificationsDropDown
     */
    public function getNotificationsDropDown(): NotificationsDropDown
    {
        if (!isset($this->notifications)) {
            $this->notifications = NotificationsDropDown::new();
        }

        return $this->notifications;
    }


    /**
     * Sets the notifications drop-down object
     *
     * @param NotificationsDropDown $notifications
     * @return static
     */
    public function setNotificationsDropDown(NotificationsDropDown $notifications): static
    {
        $this->notifications = $notifications;
        return $this;
    }


    /**
     * Returns the notifications drop-down object
     *
     * @return MessagesDropDown
     */
    public function getMessagesDropDown(): MessagesDropDown
    {
        if (!isset($this->messages)) {
            $this->messages = MessagesDropDown::new();
        }
        return $this->messages;
    }


    /**
     * Sets the notifications drop-down object
     *
     * @param MessagesDropDown $messages
     * @return static
     */
    public function setMessagesDropDown(MessagesDropDown $messages): static
    {
        $this->messages = $messages;
        return $this;
    }


    /**
     * Returns the notifications drop-down object
     *
     * @return LanguagesDropDown
     */
    public function getLanguagesDropDown(): LanguagesDropDown
    {
        if (!isset($this->languages)) {
            $this->languages = LanguagesDropDown::new();
        }
        return $this->languages;
    }


    /**
     * Sets the notifications drop-down object
     *
     * @param LanguagesDropDown $languages
     * @return static
     */
    public function setLanguagesDropDown(LanguagesDropDown $languages): static
    {
        $this->languages = $languages;
        return $this;
    }
}
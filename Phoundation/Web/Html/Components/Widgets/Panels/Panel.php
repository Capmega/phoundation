<?php

/**
 * Phoundation Panel class
 *
 * This standard webinterface class contains the basic functionalities to render web panels
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Panels;

use PDOStatement;
use Phoundation\Content\Images\ImageFile;
use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Web\Html\Components\Avatar;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Icons\Icon;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Logo;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumbs;
use Phoundation\Web\Html\Components\Widgets\ImageMenu;
use Phoundation\Web\Html\Components\Widgets\Interfaces\BreadcrumbsInterface;
use Phoundation\Web\Html\Components\Widgets\LanguagesDropDown;
use Phoundation\Web\Html\Components\Widgets\Menus\Interfaces\MenuInterface;
use Phoundation\Web\Html\Components\Widgets\Menus\Menu;
use Phoundation\Web\Html\Components\Widgets\MessagesDropDown;
use Phoundation\Web\Html\Components\Widgets\Modals\Modals;
use Phoundation\Web\Html\Components\Widgets\NotificationsDropDown;
use Phoundation\Web\Html\Components\Widgets\Panels\Interfaces\PanelInterface;
use Phoundation\Web\Html\Components\Widgets\ProfileImage;
use Phoundation\Web\Html\Enums\EnumBootstrapColor;
use Phoundation\Web\Html\Traits\TraitBootstrapColor;
use Phoundation\Web\Html\Traits\TraitMode;


abstract class Panel extends ElementsBlock implements PanelInterface
{
    use TraitMode;
    use TraitBootstrapColor;


    /**
     * Tracks the elements in this panel
     *
     * @var IteratorInterface
     */
    protected IteratorInterface $o_elements;

    /**
     * The top notifications drop down
     *
     * @var NotificationsDropDown $o_notifications
     */
    protected NotificationsDropDown $o_notifications;

    /**
     * The top messages drop down
     *
     * @var MessagesDropDown $o_messages
     */
    protected MessagesDropDown $o_messages;

    /**
     * The top languages drop down
     *
     * @var LanguagesDropDown $o_languages
     */
    protected LanguagesDropDown $o_languages;

    /**
     *
     *
     * @var IteratorInterface $o_logos
     */
    protected IteratorInterface $o_logos;

    /**
     *
     *
     * @var IteratorInterface $menus
     */
    protected IteratorInterface $menus;

    /**
     *
     *
     * @var IteratorInterface $o_buttons
     */
    protected IteratorInterface $o_buttons;

    /**
     *
     *
     * @var IteratorInterface $o_breadcrumbs
     */
    protected IteratorInterface $o_breadcrumbs;

    /**
     *
     *
     * @var IteratorInterface $o_texts
     */
    protected IteratorInterface $o_texts;

    /**
     *
     *
     * @var IteratorInterface $o_avatars
     */
    protected IteratorInterface $o_avatars;

    /**
     *
     *
     * @var IteratorInterface $o_icons
     */
    protected IteratorInterface $o_icons;

    /**
     * Modals for this panel
     *
     * @var Modals $o_modals
     */
    protected Modals $o_modals;


    /**
     * Panel class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        parent::__construct($source);
        $this->background_color = EnumBootstrapColor::primary;
    }


    /**
     * Returns the logos iterator
     *
     * @return IteratorInterface
     */
    public function getLogosObject(): IteratorInterface
    {
        if (empty($this->o_logos)) {
            $this->o_logos = Iterator::new()->setAcceptedDataTypes(Logo::class);
        }

        return $this->o_logos;
    }


    /**
     * Returns the menus iterator
     *
     * @return IteratorInterface
     */
    public function getMenusObject(): IteratorInterface
    {
        if (empty($this->menus)) {
            $this->menus = Iterator::new()->setAcceptedDataTypes(Menu::class);
        }

        return $this->menus;
    }


    /**
     * Returns the buttons iterator
     *
     * @return IteratorInterface
     */
    public function getButtonsObject(): IteratorInterface
    {
        if (empty($this->o_buttons)) {
            $this->o_buttons = Iterator::new()->setAcceptedDataTypes(Button::class);
        }

        return $this->o_buttons;
    }


    /**
     * Returns the breadcrumbs iterator
     *
     * @return BreadcrumbsInterface
     */
    public function getBreadcrumbsObject(): BreadcrumbsInterface
    {
        if (empty($this->o_breadcrumbs)) {
            $this->o_breadcrumbs = Breadcrumbs::new();
        }

        return $this->o_breadcrumbs;
    }


    /**
     * Returns the texts iterator
     *
     * @return IteratorInterface
     */
    public function getTextsObject(): IteratorInterface
    {
        if (empty($this->o_texts)) {
            $this->o_texts = Iterator::new()->setAcceptedDataTypes('string');
        }

        return $this->o_texts;
    }


    /**
     * Returns the avatars iterator
     *
     * @return IteratorInterface
     */
    public function getAvatarsObject(): IteratorInterface
    {
        if (empty($this->o_avatars)) {
            $this->o_avatars = Iterator::new()->setAcceptedDataTypes(Avatar::class);
        }

        return $this->o_avatars;
    }


    /**
     * Returns the icons iterator
     *
     * @return IteratorInterface
     */
    public function getIcons(): IteratorInterface
    {
        if (empty($this->o_icons)) {
            $this->o_icons = Iterator::new()->setAcceptedDataTypes(Icon::class);
        }

        return $this->o_icons;
    }


    /**
     * Sets the panel menu
     *
     * @param MenuInterface|null $o_menu
     *
     * @return static
     */
    public function setMenu(?MenuInterface $o_menu): static
    {
        $this->source['menu'] = $o_menu;
        return $this;
    }


    /**
     * Returns the panel menu
     *
     * @return MenuInterface|null
     */
    public function getMenu(): ?MenuInterface
    {
        return array_get_safe($this->source, 'menu');
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
     * @param ImageMenu $o_profile_image
     *
     * @return static
     */
    public function setProfileImage(ImageMenu $o_profile_image): static
    {
        $this->source['profile_image'] = $o_profile_image;
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
     * @param ImageFileInterface|string $o_logo
     *
     * @return static
     */
    public function setLogo(ImageFileInterface|string $o_logo): static
    {
        if (is_string($o_logo)) {
            $o_logo = ImageFile::new($o_logo);
        }

        $this->source['logo'] = $o_logo;
        return $this;
    }


    /**
     * Returns the panel modals
     *
     * @return Modals
     */
    public function getModals(): Modals
    {
        if (!isset($this->o_modals)) {
            $this->o_modals = new Modals();
        }

        return $this->o_modals;
    }


    /**
     * Access to the elements object
     *
     * @return IteratorInterface
     */
    public function getElementsObject(): IteratorInterface
    {
        return $this->o_elements;
    }


    /**
     * Access to the elements object
     *
     * @param IteratorInterface|array $o_elements
     *
     * @return TopPanel
     */
    public function setElementsObject(IteratorInterface|array $o_elements): static
    {
        $this->o_elements = new Iterator($o_elements);
        return $this;
    }


    /**
     * Returns the notifications drop-down object
     *
     * @return NotificationsDropDown
     */
    public function getNotificationsDropDownObject(): NotificationsDropDown
    {
        if (!isset($this->o_notifications)) {
            $this->o_notifications = NotificationsDropDown::new();
        }

        return $this->o_notifications;
    }


    /**
     * Sets the notifications drop-down object
     *
     * @param NotificationsDropDown $o_notifications
     *
     * @return static
     */
    public function setNotificationsDropDownObject(NotificationsDropDown $o_notifications): static
    {
        $this->o_notifications = $o_notifications;
        return $this;
    }


    /**
     * Returns the notifications drop-down object
     *
     * @return MessagesDropDown
     */
    public function getMessagesDropDownObject(): MessagesDropDown
    {
        if (!isset($this->o_messages)) {
            $this->o_messages = MessagesDropDown::new();
        }

        return $this->o_messages;
    }


    /**
     * Sets the notifications drop-down object
     *
     * @param MessagesDropDown $o_messages
     *
     * @return static
     */
    public function setMessagesDropDownObject(MessagesDropDown $o_messages): static
    {
        $this->o_messages = $o_messages;
        return $this;
    }


    /**
     * Returns the notifications drop-down object
     *
     * @return LanguagesDropDown
     */
    public function getLanguagesDropDownObject(): LanguagesDropDown
    {
        if (!isset($this->o_languages)) {
            $this->o_languages = LanguagesDropDown::new();
        }

        return $this->o_languages;
    }


    /**
     * Sets the notifications drop-down object
     *
     * @param LanguagesDropDown $o_languages
     *
     * @return static
     */
    public function setLanguagesDropDownObject(LanguagesDropDown $o_languages): static
    {
        $this->o_languages = $o_languages;
        return $this;
    }
}

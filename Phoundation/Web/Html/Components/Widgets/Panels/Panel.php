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
    protected IteratorInterface $_elements;

    /**
     * The top notifications drop down
     *
     * @var NotificationsDropDown $_notifications
     */
    protected NotificationsDropDown $_notifications;

    /**
     * The top messages drop down
     *
     * @var MessagesDropDown $_messages
     */
    protected MessagesDropDown $_messages;

    /**
     * The top languages drop down
     *
     * @var LanguagesDropDown $_languages
     */
    protected LanguagesDropDown $_languages;

    /**
     *
     *
     * @var IteratorInterface $_logos
     */
    protected IteratorInterface $_logos;

    /**
     *
     *
     * @var IteratorInterface $menus
     */
    protected IteratorInterface $menus;

    /**
     *
     *
     * @var IteratorInterface $_buttons
     */
    protected IteratorInterface $_buttons;

    /**
     *
     *
     * @var IteratorInterface $_breadcrumbs
     */
    protected IteratorInterface $_breadcrumbs;

    /**
     *
     *
     * @var IteratorInterface $_texts
     */
    protected IteratorInterface $_texts;

    /**
     *
     *
     * @var IteratorInterface $_avatars
     */
    protected IteratorInterface $_avatars;

    /**
     *
     *
     * @var IteratorInterface $_icons
     */
    protected IteratorInterface $_icons;

    /**
     * Modals for this panel
     *
     * @var Modals $_modals
     */
    protected Modals $_modals;


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
        if (empty($this->_logos)) {
            $this->_logos = Iterator::new()->setAcceptedDataTypes(Logo::class);
        }

        return $this->_logos;
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
        if (empty($this->_buttons)) {
            $this->_buttons = Iterator::new()->setAcceptedDataTypes(Button::class);
        }

        return $this->_buttons;
    }


    /**
     * Returns the breadcrumbs iterator
     *
     * @return BreadcrumbsInterface
     */
    public function getBreadcrumbsObject(): BreadcrumbsInterface
    {
        if (empty($this->_breadcrumbs)) {
            $this->_breadcrumbs = Breadcrumbs::new();
        }

        return $this->_breadcrumbs;
    }


    /**
     * Returns the texts iterator
     *
     * @return IteratorInterface
     */
    public function getTextsObject(): IteratorInterface
    {
        if (empty($this->_texts)) {
            $this->_texts = Iterator::new()->setAcceptedDataTypes('string');
        }

        return $this->_texts;
    }


    /**
     * Returns the avatars iterator
     *
     * @return IteratorInterface
     */
    public function getAvatarsObject(): IteratorInterface
    {
        if (empty($this->_avatars)) {
            $this->_avatars = Iterator::new()->setAcceptedDataTypes(Avatar::class);
        }

        return $this->_avatars;
    }


    /**
     * Returns the icons iterator
     *
     * @return IteratorInterface
     */
    public function getIcons(): IteratorInterface
    {
        if (empty($this->_icons)) {
            $this->_icons = Iterator::new()->setAcceptedDataTypes(Icon::class);
        }

        return $this->_icons;
    }


    /**
     * Sets the panel menu
     *
     * @param MenuInterface|null $_menu
     *
     * @return static
     */
    public function setMenu(?MenuInterface $_menu): static
    {
        $this->source['menu'] = $_menu;
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
     * @param ImageMenu $_profile_image
     *
     * @return static
     */
    public function setProfileImage(ImageMenu $_profile_image): static
    {
        $this->source['profile_image'] = $_profile_image;
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
     * @param ImageFileInterface|string $_logo
     *
     * @return static
     */
    public function setLogo(ImageFileInterface|string $_logo): static
    {
        if (is_string($_logo)) {
            $_logo = ImageFile::new($_logo);
        }

        $this->source['logo'] = $_logo;
        return $this;
    }


    /**
     * Returns the panel modals
     *
     * @return Modals
     */
    public function getModals(): Modals
    {
        if (!isset($this->_modals)) {
            $this->_modals = new Modals();
        }

        return $this->_modals;
    }


    /**
     * Access to the elements object
     *
     * @return IteratorInterface
     */
    public function getElementsObject(): IteratorInterface
    {
        return $this->_elements;
    }


    /**
     * Access to the elements object
     *
     * @param IteratorInterface|array $_elements
     *
     * @return TopPanel
     */
    public function setElementsObject(IteratorInterface|array $_elements): static
    {
        $this->_elements = new Iterator($_elements);
        return $this;
    }


    /**
     * Returns the notifications drop-down object
     *
     * @return NotificationsDropDown
     */
    public function getNotificationsDropDownObject(): NotificationsDropDown
    {
        if (!isset($this->_notifications)) {
            $this->_notifications = NotificationsDropDown::new();
        }

        return $this->_notifications;
    }


    /**
     * Sets the notifications drop-down object
     *
     * @param NotificationsDropDown $_notifications
     *
     * @return static
     */
    public function setNotificationsDropDownObject(NotificationsDropDown $_notifications): static
    {
        $this->_notifications = $_notifications;
        return $this;
    }


    /**
     * Returns the notifications drop-down object
     *
     * @return MessagesDropDown
     */
    public function getMessagesDropDownObject(): MessagesDropDown
    {
        if (!isset($this->_messages)) {
            $this->_messages = MessagesDropDown::new();
        }

        return $this->_messages;
    }


    /**
     * Sets the notifications drop-down object
     *
     * @param MessagesDropDown $_messages
     *
     * @return static
     */
    public function setMessagesDropDownObject(MessagesDropDown $_messages): static
    {
        $this->_messages = $_messages;
        return $this;
    }


    /**
     * Returns the notifications drop-down object
     *
     * @return LanguagesDropDown
     */
    public function getLanguagesDropDownObject(): LanguagesDropDown
    {
        if (!isset($this->_languages)) {
            $this->_languages = LanguagesDropDown::new();
        }

        return $this->_languages;
    }


    /**
     * Sets the notifications drop-down object
     *
     * @param LanguagesDropDown $_languages
     *
     * @return static
     */
    public function setLanguagesDropDownObject(LanguagesDropDown $_languages): static
    {
        $this->_languages = $_languages;
        return $this;
    }
}

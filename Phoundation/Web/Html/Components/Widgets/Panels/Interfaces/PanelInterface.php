<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Panels\Interfaces;

use PDOStatement;
use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Components\Widgets\ImageMenu;
use Phoundation\Web\Html\Components\Widgets\LanguagesDropDown;
use Phoundation\Web\Html\Components\Widgets\Menus\Interfaces\MenuInterface;
use Phoundation\Web\Html\Components\Widgets\MessagesDropDown;
use Phoundation\Web\Html\Components\Widgets\Modals\Modals;
use Phoundation\Web\Html\Components\Widgets\NotificationsDropDown;
use Phoundation\Web\Html\Components\Widgets\Panels\TopPanel;
use Phoundation\Web\Html\Components\Widgets\ProfileImage;

interface PanelInterface extends ElementsBlockInterface
{
    /**
     * Set the panel source and ensure all URL's are absolute
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static;


    /**
     * Sets the panel menu
     *
     * @param MenuInterface|null $o_menu
     *
     * @return static
     */
    public function setMenu(?MenuInterface $o_menu): static;


    /**
     * Returns the panel menu
     *
     * @return MenuInterface|null
     */
    public function getMenu(): ?MenuInterface;


    /**
     * Returns the panel profile image
     *
     * @return ProfileImage|null
     */
    public function getProfileImage(): ?ProfileImage;


    /**
     * Sets the panel profile image
     *
     * @param ImageMenu $o_profile_image
     *
     * @return static
     */
    public function setProfileImage(ImageMenu $o_profile_image): static;


    /**
     * Returns the panel logo
     *
     * @return string|null
     */
    public function getLogo(): ?string;


    /**
     * Sets the panel profile image
     *
     * @param ImageFileInterface|string $o_logo
     *
     * @return static
     */
    public function setLogo(ImageFileInterface|string $o_logo): static;


    /**
     * Returns the panel modals
     *
     * @return Modals
     */
    public function getModals(): Modals;


    /**
     * Access to the elements object
     *
     * @return IteratorInterface
     */
    public function getElementsObject(): IteratorInterface;


    /**
     * Access to the elements object
     *
     * @param IteratorInterface|array $o_elements
     *
     * @return TopPanel
     */
    public function setElementsObject(IteratorInterface|array $o_elements): static;


    /**
     * Returns the notifications drop-down object
     *
     * @return NotificationsDropDown
     */
    public function getNotificationsDropDownObject(): NotificationsDropDown;


    /**
     * Sets the notifications drop-down object
     *
     * @param NotificationsDropDown $o_notifications
     *
     * @return static
     */
    public function setNotificationsDropDownObject(NotificationsDropDown $o_notifications): static;


    /**
     * Returns the notifications drop-down object
     *
     * @return MessagesDropDown
     */
    public function getMessagesDropDownObject(): MessagesDropDown;


    /**
     * Sets the notifications drop-down object
     *
     * @param MessagesDropDown $o_messages
     *
     * @return static
     */
    public function setMessagesDropDownObject(MessagesDropDown $o_messages): static;


    /**
     * Returns the notifications drop-down object
     *
     * @return LanguagesDropDown
     */
    public function getLanguagesDropDownObject(): LanguagesDropDown;


    /**
     * Sets the notifications drop-down object
     *
     * @param LanguagesDropDown $o_languages
     *
     * @return static
     */
    public function setLanguagesDropDownObject(LanguagesDropDown $o_languages): static;
}

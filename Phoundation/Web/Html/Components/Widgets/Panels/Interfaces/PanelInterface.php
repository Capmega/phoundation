<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Panels\Interfaces;

use PDOStatement;
use Phoundation\Content\Images\Interfaces\ImageInterface;
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

/**
 * Panel class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Web
 */
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
     * @param MenuInterface|null $menu
     *
     * @return static
     */
    public function setMenu(?MenuInterface $menu): static;


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
     * @param ImageMenu $profile_image
     *
     * @return static
     */
    public function setProfileImage(ImageMenu $profile_image): static;


    /**
     * Returns the panel logo
     *
     * @return string|null
     */
    public function getLogo(): ?string;


    /**
     * Sets the panel profile image
     *
     * @param ImageInterface|string $logo
     *
     * @return static
     */
    public function setLogo(ImageInterface|string $logo): static;


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
     * @param IteratorInterface|array $elements
     *
     * @return TopPanel
     */
    public function setElementsObject(IteratorInterface|array $elements): static;


    /**
     * Returns the notifications drop-down object
     *
     * @return NotificationsDropDown
     */
    public function getNotificationsDropDown(): NotificationsDropDown;


    /**
     * Sets the notifications drop-down object
     *
     * @param NotificationsDropDown $notifications
     *
     * @return static
     */
    public function setNotificationsDropDown(NotificationsDropDown $notifications): static;


    /**
     * Returns the notifications drop-down object
     *
     * @return MessagesDropDown
     */
    public function getMessagesDropDown(): MessagesDropDown;


    /**
     * Sets the notifications drop-down object
     *
     * @param MessagesDropDown $messages
     *
     * @return static
     */
    public function setMessagesDropDown(MessagesDropDown $messages): static;


    /**
     * Returns the notifications drop-down object
     *
     * @return LanguagesDropDown
     */
    public function getLanguagesDropDown(): LanguagesDropDown;


    /**
     * Sets the notifications drop-down object
     *
     * @param LanguagesDropDown $languages
     *
     * @return static
     */
    public function setLanguagesDropDown(LanguagesDropDown $languages): static;
}
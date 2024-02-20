<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Web\Http\UrlBuilder;


/**
 * TopPanel class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class TopPanel extends Panel
{
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
     * TopPanel class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        // Set the default menu for top panels
        $this->source['menu'] = Menu::new()->addSources([
            tr('Home') => (string) UrlBuilder::getCurrentDomainRootUrl()
        ]);

        if (Session::getUser()->hasAllRights('demos')) {
            $this->source['menu']->add((string) UrlBuilder::getWww('demos.html'), tr('Demos'));
        }

        parent::__construct($content);

        $this->elements = Iterator::new()->setSource([
            'search',
            'notifications',
            'full-screen',
            'sign-out'
        ]);
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
     * Returns the notifications drop down object
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
     * Sets the notifications drop down object
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
     * Returns the notifications drop down object
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
     * Sets the notifications drop down object
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
     * Returns the notifications drop down object
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
     * Sets the notifications drop down object
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

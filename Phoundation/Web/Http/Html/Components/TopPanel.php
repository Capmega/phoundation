<?php

namespace Phoundation\Web\Http\Html\Components;



/**
 * TopPanel class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class TopPanel extends Panel
{
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
}
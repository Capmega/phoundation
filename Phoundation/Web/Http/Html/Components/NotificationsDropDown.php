<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Notifications\Notifications;
use Phoundation\Web\Http\UrlBuilder;



/**
 * NotificationsDropDown class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class NotificationsDropDown extends ElementsBlock
{
    /**
     * The list of notifications
     *
     * @var Notifications|null $notifications
     */
    protected ?Notifications $notifications = null;

    /**
     * Contains the URL for the specific notifications
     *
     * @var string|null $notifications_url
     */
    protected ?string $notifications_url = null;

    /**
     * Contains the URL for the notifications page
     *
     * @var string|null $notifications_all_url
     */
    protected ?string $notifications_all_url = null;



    /**
     * Returns the notifications object
     *
     * @return Notifications|null
     */
    public function getNotifications(?string $status): ?Notifications
    {
        if (!$this->notifications) {
            $this->notifications = new Notifications();
            $this->notifications->loadList(null, ['status' => $status]);
        }

        return $this->notifications;
    }



    /**
     * Sets the notifications object
     *
     * @param Notifications|null $notifications
     * @return static
     */
    public function setNotifications(?Notifications $notifications): static
    {
        $this->notifications = $notifications;
        return $this;
    }



    /**
     * Returns the notifications page URL
     *
     * @return string|null
     */
    public function getNotificationsUrl(): ?string
    {
        return $this->notifications_url;
    }



    /**
     * Sets the notifications page URL
     *
     * @param string|null $notifications_url
     * @return static
     */
    public function setNotificationsUrl(?string $notifications_url): static
    {
        $this->notifications_url = UrlBuilder::getWww($notifications_url);
        return $this;
    }



    /**
     * Returns the notifications page URL
     *
     * @return string|null
     */
    public function getAllNotificationsUrl(): ?string
    {
        return $this->notifications_all_url;
    }



    /**
     * Sets the notifications page URL
     *
     * @param string|null $notifications_url
     * @return static
     */
    public function setAllNotificationsUrl(?string $notifications_url): static
    {
        $this->notifications_all_url = UrlBuilder::getWww($notifications_url);
        return $this;
    }
}
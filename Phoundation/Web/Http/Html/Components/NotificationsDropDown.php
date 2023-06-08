<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Core\Session;
use Phoundation\Notifications\Notifications;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;


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
     * @var Stringable|string|null $notifications_url
     */
    protected Stringable|string|null $notifications_url = null;

    /**
     * Contains the URL for the notifications page
     *
     * @var Stringable|string|null $notifications_all_url
     */
    protected Stringable|string|null $notifications_all_url = null;


    /**
     * Returns the notifications object
     *
     * @param string|null $status
     * @return Notifications|null
     */
    public function getNotifications(?string $status): ?Notifications
    {
        if (!$this->notifications) {
            $this->notifications = new Notifications();
            $this->notifications->loadList(null, [
                'users_id' => Session::getUser()->getId(),
                'status'   => $status
            ]);
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
     * @return Stringable|string|null
     */
    public function getNotificationsUrl(): Stringable|string|null
    {
        return $this->notifications_url;
    }


    /**
     * Sets the notifications page URL
     *
     * @param Stringable|string|null $notifications_url
     * @return static
     */
    public function setNotificationsUrl(Stringable|string|null $notifications_url): static
    {
        $this->notifications_url = UrlBuilder::getWww($notifications_url);
        return $this;
    }


    /**
     * Returns the notifications page URL
     *
     * @return Stringable|string|null
     */
    public function getAllNotificationsUrl(): Stringable|string|null
    {
        return $this->notifications_all_url;
    }


    /**
     * Sets the notifications page URL
     *
     * @param Stringable|string|null $notifications_url
     * @return static
     */
    public function setAllNotificationsUrl(Stringable|string|null $notifications_url): static
    {
        $this->notifications_all_url = UrlBuilder::getWww($notifications_url);
        return $this;
    }
}
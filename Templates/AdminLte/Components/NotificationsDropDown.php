<?php

namespace Templates\AdminLte\Components;

use Phoundation\Exception\OutOfBoundsException;
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
 * @package Templates\AdminLte
 */
class NotificationsDropDown extends \Phoundation\Web\Http\Html\Components\NotificationsDropDown
{
    /**
     * The list of notifications
     *
     * @var Notifications|null $notifications
     */
    protected ?Notifications $notifications = null;

    /**
     * Contains the URL for the notifications page
     *
     * @var string|null $notifications_url
     */
    protected ?string $notifications_url = null;



    /**
     * Returns the notifications object
     *
     * @return Notifications|null
     */
    public function getNotifications(): ?Notifications
    {
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
        $this->notifications_url = UrlBuilder::www($notifications_url);
        return $this;
    }



    /**
     * Renders and returns the NavBar
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!isset($this->notifications_url)) {
            throw new OutOfBoundsException(tr('No notifications page URL specified'));
        }

        if ($this->notifications) {
            $count = $this->notifications->count();
        } else {
            $count = 0;
        }

        $this->render = '   <a class="nav-link" data-toggle="dropdown" href="#">
                              <i class="far fa-bell"></i>
                              ' . ($count ? '<span class="badge badge-warning navbar-badge">' . $count . '</span>' : null) . '                              
                            </a>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                  <span class="dropdown-item dropdown-header">' . tr(':count Notifications', [':count' => $count]) . '</span>
                                  <div class="dropdown-divider"></div>';

        if ($count) {
            foreach ($this->notifications as $notification) {
                $this->render .= '<a href="' . $notification->getUrl() . '" class="dropdown-item">
                                    <i class="fas fa-' . $notification->getIcon() . ' mr-2"></i> ' . $notification->getShortMessage() . '
                                    <span class="float-right text-muted text-sm"> ' . $notification->getAge() . '</span>
                                  </a>
                                  <div class="dropdown-divider"></div>';
            }
        }

        $this->render .= '        <a href="' . $this->notifications_url . '" class="dropdown-item dropdown-footer">' . tr('See All Notifications') . '</a>
                                </div>';

        return parent::render();
    }
}
<?php

namespace Templates\AdminLte\Html\Components;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Renderer;


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
class NotificationsDropDown extends Renderer
{
    /**
     * NotificationsDropDown class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\NotificationsDropDown $element)
    {
        parent::__construct($element);
    }



    /**
     * Renders and returns the NavBar
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->element->getNotificationsUrl()) {
            throw new OutOfBoundsException(tr('No notifications page URL specified'));
        }

        if ($this->element->getNotifications()) {
            $count = $this->element->getNotifications()->count();
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
            foreach ($this->element->getNotifications() as $notification) {
                $this->render .= '<a href="' . $notification->getUrl() . '" class="dropdown-item">
                                    <i class="fas fa-' . $notification->getIcon() . ' mr-2"></i> ' . $notification->getShortMessage() . '
                                    <span class="float-right text-muted text-sm"> ' . $notification->getAge() . '</span>
                                  </a>
                                  <div class="dropdown-divider"></div>';
            }
        }

        $this->render .= '        <a href="' . $this->element->getNotificationsUrl() . '" class="dropdown-item dropdown-footer">' . tr('See All Notifications') . '</a>
                                </div>';

        return parent::render();
    }
}
<?php

declare(strict_types=1);


namespace Templates\None\Html\Components;

use Phoundation\Core\Strings;
use Phoundation\Date\Date;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;

/**
 * NotificationsDropDown class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
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
        if (!$this->element->getAllNotificationsUrl()) {
            throw new OutOfBoundsException(tr('No all notifications page URL specified'));
        }

        if (!$this->element->getNotificationsUrl()) {
            throw new OutOfBoundsException(tr('No notifications page URL specified'));
        }

        $notifications = $this->element->getNotifications(null);

        if ($notifications) {
            $count = $notifications->getCount();
            $mode  = $notifications->getMostImportantMode();
            $mode  = strtolower($mode);
        } else {
            $count = 0;
        }

        $this->render = '   <a class="nav-link" data-toggle="dropdown" href="#">
                              <i class="far fa-bell"></i>
                              ' . ($count ? '<span class="badge badge-' . Html::safe($mode) . ' navbar-badge">' . Html::safe($count) . '</span>' : null) . '                              
                            </a>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                  <span class="dropdown-item dropdown-header">' . tr(':count Notifications', [':count' => $count]) . '</span>
                                  <div class="dropdown-divider"></div>';

        if ($count) {
            $current = 0;

            foreach ($notifications as $notification) {
                if (++$current > 12) {
                    break;
                }

                $this->render .= '<a href="' . Html::safe(str_replace(':ID', $notification->getId(), $this->element->getNotificationsUrl())) . '" class="dropdown-item">
                                    ' . ($notification->getIcon() ? '<i class="text-' . Html::safe($notification->getMode()->value) . ' fas fa-' . Html::safe($notification->getIcon()) . ' mr-2"></i> ' : null) . Html::safe(Strings::truncate($notification->getTitle()), 24) . '
                                    <span class="float-right text-muted text-sm"> ' . Html::safe(Date::getAge($notification->getCreatedOn())) . '</span>
                                  </a>
                                  <div class="dropdown-divider"></div>';
            }
        }

        $this->render .= '        <a href="' . Html::safe($this->element->getAllNotificationsUrl()) . '" class="dropdown-item dropdown-footer">' . tr('See All Notifications') . '</a>
                                </div>';

        return parent::render();
    }
}
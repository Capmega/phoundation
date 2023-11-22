<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components;

use Phoundation\Date\Date;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Html\Components\Modals\NotificationModal;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Html\Renderer;


/**
 * NotificationsDropDown class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class NotificationsDropDown extends Renderer
{
    /**
     * NotificationsDropDown class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\NotificationsDropDown $element)
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
        if (!$this->render_object->getAllNotificationsUrl()) {
            throw new OutOfBoundsException(tr('No all notifications page URL specified'));
        }

        if (!$this->render_object->getNotificationsUrl()) {
            throw new OutOfBoundsException(tr('No notifications page URL specified'));
        }

        $notifications = $this->render_object->getNotifications();

        if ($notifications) {
            $notifications->autoUpdate();

            $count = $notifications->getCount();
            $mode = $notifications->getMostImportantMode();
            $mode = strtolower($mode);

            // With HTML, "notice" and "information" are known as "info"
            $mode = match ($mode) {
                'unknown', 'notice', 'information' => 'info',
                default => $mode
            };

        } else {
            $count = 0;
        }

        $this->render = '   <a class="nav-link" data-toggle="dropdown" href="#">
                              <i class="far fa-bell"></i>
                              ' . ($count ? '<span class="badge badge-' . Html::safe($mode) . ' navbar-badge">' . Html::safe($count) . '</span>' : null) . '                              
                            </a>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                  <span class="dropdown-item dropdown-header">' . tr(':count Notifications', [':count' => ($count > 99 ? '99+' : $count)]) . '</span>
                                  <div class="dropdown-divider"></div>';

        if ($count) {
            $current = 0;

            foreach ($notifications as $notification) {
                if (++$current > 12) {
                    break;
                }

                $this->render .= '<a href="' . Html::safe(str_replace(':ID', (string) $notification->getId(), (string) $this->render_object->getNotificationsUrl())) . '" class="dropdown-item notification open-modal" data-id="' . $notification->getId() . '">
                                    ' . ($notification->getIcon() ? '<i class="text-' . Html::safe($notification->getMode()->value) . ' fas fa-' . Html::safe($notification->getIcon()) . ' mr-2"></i> ' : null) . Html::safe(Strings::truncate($notification->getTitle(), 24)) . '
                                    <span class="float-right text-muted text-sm"> ' . Html::safe(Date::getAge($notification->getCreatedOn())) . '</span>
                                  </a>
                                  <div class="dropdown-divider"></div>';
            }
        }

        $this->render .= '        <a href="' . Html::safe($this->render_object->getAllNotificationsUrl()) . '" class="dropdown-item dropdown-footer">' . tr('See all unread notifications') . '</a>
                                </div>';

        return parent::render() . NotificationModal::new()->render();
    }
}

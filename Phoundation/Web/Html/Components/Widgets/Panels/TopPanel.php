<?php

/**
 * TopPanel class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Panels;

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Iterator;
use Phoundation\Web\Html\Components\Widgets\Menus\Menu;
use Phoundation\Web\Http\Url;


class TopPanel extends Panel
{
    /**
     * TopPanel class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        // Set the default menu for top panels
        $this->source['menu'] = Menu::new()
                                    ->addSource([
                                        tr('Home') => (string) Url::getCurrentDomainRootUrl(),
                                    ]);
        if (
            Session::getUserObject()
                   ->hasAllRights('demos')
        ) {
            $this->source['menu']->add((string) Url::getWww('demos.html'), tr('Demos'));
        }
        parent::__construct($content);
        $this->elements = Iterator::new([
            'search',
            'notifications',
            'languages',
            'full-screen',
            'sign-out',
        ]);
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        if ($this->elements->valueExists('notifications')) {
            $this->getNotificationsDropDown()
                 ->setStatusFilter('UNREAD')
                 ->setNotifications(null)
                 ->setNotificationsUrl('/notifications/notification-:ID.html')
                 ->setAllNotificationsUrl('/notifications/unread.html');
        }
        if ($this->elements->valueExists('messages')) {
            $this->getMessagesDropDown()
                 ->setMessages(null)
                 ->setMessagesUrl('/messages/unread.html');
        }
        if ($this->elements->valueExists('languages')) {
            $this->getLanguagesDropDown()
                 ->setLanguages(null)
                 ->setSettingsUrl('/settings.html');
        }

        return parent::render();
    }
}

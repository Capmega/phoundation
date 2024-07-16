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
use Phoundation\Web\Http\UrlBuilder;

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
                                    ->addSources([
                                        tr('Home') => (string) UrlBuilder::getCurrentDomainRootUrl(),
                                    ]);
        if (
            Session::getUser()
                   ->hasAllRights('demos')
        ) {
            $this->source['menu']->add((string) UrlBuilder::getWww('demos.html'), tr('Demos'));
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
                 ->setStatus('UNREAD')
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

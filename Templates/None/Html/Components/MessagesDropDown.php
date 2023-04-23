<?php

namespace Templates\None\Html\Components;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Renderer;


/**
 * None Plugin MessagesDropDown class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class MessagesDropDown extends Renderer
{
    /**
     * MessagesDropDown class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\MessagesDropDown $element)
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
        if (!$this->element->getMessagesUrl()) {
            throw new OutOfBoundsException(tr('No messages page URL specified'));
        }

        if ($this->element->getMessages()) {
            $count = $this->element->getMessages()->count();
        } else {
            $count = 0;
        }

        $this->render = '       <a class="nav-link" data-toggle="dropdown" href="#">
                                  <i class="far fa-comments"></i>
                                  ' . ($count ? '<span class="badge badge-danger navbar-badge">' . $count . '</span>' : null) .  '
                                </a>
                                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                  <span class="dropdown-item dropdown-header">' . tr(':count Messages', [':count' => $count]) . '</span>
                                  <div class="dropdown-divider"></div>';

        if ($count) {
            foreach ($this->element->getMessages() as $message) {
                $this->render . -'<a href="' . $message->getUrl() . '" class="dropdown-item">
                                    <!-- Message Start -->
                                    <div class="media">
                                      <img src="' . $message->getAvatar() . '" alt="' . tr('Avatar for :user', [':user' => $message->getDisplayName()]) . '" class="img-size-50 mr-3 img-circle">
                                      <div class="media-body">
                                        <h3 class="dropdown-item-title">
                                          ' . $message->getDisplayName() . '
                                          ' . ($message->getStar() ? '<span class="float-right text-sm text-' . $message->getStar() . '"><i class="fas fa-star"></i></span>' : null) . '                                          
                                        </h3>
                                        <p class="text-sm">' . $message->getMessageHeader() . '</p>
                                        <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> ' . $message->getAge() . '</p>
                                      </div>
                                    </div>
                                    <!-- Message End -->
                                  </a>
                                  <div class="dropdown-divider"></div>';
            }
        }

        $this->render .= '        
                                  <a href="' . $this->element->getMessagesUrl() . '" class="dropdown-item dropdown-footer">' . tr('See All Messages') . '</a>
                                </div>';

        return parent::render();
    }
}
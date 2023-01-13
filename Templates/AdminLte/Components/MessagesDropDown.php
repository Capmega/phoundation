<?php

namespace Templates\AdminLte\Components;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Messages\Messages;
use Phoundation\Web\Http\Url;



/**
 * AdminLte Plugin MessagesDropDown class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class MessagesDropDown extends \Phoundation\Web\Http\Html\Components\MessagesDropDown
{
    /**
     * The list of messages
     *
     * @var Messages|null $messages
     */
    protected ?Messages $messages = null;

    /**
     * Contains the URL for the messages page
     *
     * @var string|null $messages_url
     */
    protected ?string $messages_url = null;



    /**
     * Returns the messages object
     *
     * @return Messages|null
     */
    public function getMessages(): ?Messages
    {
        return $this->messages;
    }



    /**
     * Sets the messages object
     *
     * @param Messages|null $messages
     * @return static
     */
    public function setMessages(?Messages $messages): static
    {
        $this->messages = $messages;
        return $this;
    }



    /**
     * Returns the messages page URL
     *
     * @return string|null
     */
    public function getMessagesUrl(): ?string
    {
        return $this->messages_url;
    }



    /**
     * Sets the messages page URL
     *
     * @param string|null $messages_url
     * @return static
     */
    public function setMessagesUrl(?string $messages_url): static
    {
        $this->messages_url = Url::build($messages_url)->www();
        return $this;
    }



    /**
     * Renders and returns the NavBar
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!isset($this->messages_url)) {
            throw new OutOfBoundsException(tr('No messages page URL specified'));
        }

        if ($this->messages) {
            $count = $this->messages->count();
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
            foreach ($this->messages as $message) {
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
                                  <a href="' . $this->messages_url . '" class="dropdown-item dropdown-footer">' . tr('See All Messages') . '</a>
                                </div>';

        return parent::render();
    }
}
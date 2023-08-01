<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Messages\Messages;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;


/**
 * MessagesDropDown class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class MessagesDropDown extends ElementsBlock
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
     * @var Stringable|string|null $messages_url
     */
    protected Stringable|string|null $messages_url = null;


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
     * @return Stringable|string|null
     */
    public function getMessagesUrl(): Stringable|string|null
    {
        return $this->messages_url;
    }


    /**
     * Sets the messages page URL
     *
     * @param Stringable|string|null $messages_url
     * @return static
     */
    public function setMessagesUrl(Stringable|string|null $messages_url): static
    {
        $this->messages_url = UrlBuilder::getWww($messages_url);
        return $this;
    }
}
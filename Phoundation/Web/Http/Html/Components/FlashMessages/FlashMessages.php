<?php

namespace Phoundation\Web\Http\Html\Components\FlashMessages;

use Iterator;
use Phoundation\Web\Http\Html\Components\ElementsBlock;



/**
 * Class FlashMessages
 *
 * This class tracks HTML flash messages and can render each message and return HTML code.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class FlashMessages extends ElementsBlock implements Iterator
{
    /**
     * The flash messages
     *
     * @var array $messages
     */
    protected array $messages = [];



    /**
     * This method will move all messages from the specified FlashMessages object here.
     *
     * @param FlashMessages $messages
     * @return static
     */
    public function moveMessagesFrom(FlashMessages $messages): static
    {
        foreach ($messages as $message) {
            $this->add($message);
        }

        // Clear the messages from the specified FlashMessages object
        $messages->clear();
        return $this;
    }



    /**
     * Clear all messages in this object
     *
     * @return $this
     */
    public function clear(): static
    {
        $this->messages = [];
        return $this;
    }



    /**
     * Add a flash message
     *
     * @param FlashMessage|string|null $message
     * @param string $type
     * @param string|null $icon
     * @return $this
     */
    public function add(FlashMessage|string|null $message, string $type = 'info', string $icon = null): static
    {
        if (is_string($message)) {
            $message = FlashMessage::new()
                ->setMessage($message)
                ->setType($type)
                ->setIcon($icon);
        }

        $this->messages[] = $message;
        return $this;
    }



    /**
     * Renders all flash messages
     *
     * @return string|null
     */
    public function render(): ?string
    {

    }



    /**
     * Iterator methods
     *
     * @return mixed
     */
    public function current(): mixed
    {
        return current($this->messages);
    }

    public function next(): void
    {
        next($this->messages);
    }

    public function key(): string
    {
        return key($this->messages);
    }

    public function valid(): bool
    {
        return isset($this->messages[key($this->messages)]);
    }

    public function rewind(): void
    {
        reset($this->messages);
    }
}
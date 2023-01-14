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
    public function mergeMessagesFrom(FlashMessages $messages): static
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
     * @param FlashMessage|string|null $title
     * @param string|null $message
     * @param string $type
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function add(FlashMessage|string|null $title, ?string $message = null, string $type = 'info', string $icon = null, ?int $auto_close = null): static
    {
        if (is_string($message)) {
            $message = FlashMessage::new()
                ->setAutoClose($auto_close)
                ->setMessage($message)
                ->setTitle($title)
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
        $this->render = '';

        foreach ($this->messages as $message) {
            $this->render .= $message->render();
        }

        return parent::render();
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
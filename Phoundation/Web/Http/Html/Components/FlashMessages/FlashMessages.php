<?php

namespace Phoundation\Web\Http\Html\Components\FlashMessages;

use Iterator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\ElementsBlock;
use Phoundation\Web\Http\Html\Components\Script;


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
     * This method will move all messages from the specified FlashMessages object here.
     *
     * @param FlashMessages|null $messages
     * @return static
     */
    public function pullMessagesFrom(?FlashMessages $messages): static
    {
        if ($messages) {
            foreach ($messages as $message) {
                $this->add($message);
            }

            // Clear the messages from the specified FlashMessages object
            $messages->clear();
        }

        return $this;
    }



    /**
     * Add a flash message
     *
     * @param FlashMessage|Exception|string|null $title
     * @param string|null $message
     * @param string $type
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function add(FlashMessage|Exception|string|null $title, ?string $message = null, string $type = 'info', string $icon = null, ?int $auto_close = null): static
    {
        if ($title) {
            // a title was specified
            if (is_string($title)) {
                // Title was specified as a string, make it a flash message
                if (!$message) {
                    throw new OutOfBoundsException(tr('No message specified for this flash message'));
                }

                $title = FlashMessage::new()
                    ->setAutoClose($auto_close)
                    ->setMessage($message)
                    ->setTitle($title)
                    ->setType($type)
                    ->setIcon($icon);
            } elseif ($title instanceof ValidationFailedException) {
                // Title was specified as an exception, add each validation failure as a separate flash
                // message
                if ($title->getData()) {
                    foreach ($title->getData() as $message) {
                        $this->add(tr('Information validation failure'), $message, 'warning', null, 5000);
                    }
                }

                return $this;

            } elseif ($title instanceof Exception) {
                // Title was specified as an exception, add each validation failure as a separate flash
                // message
                if ($title->getMessages()) {
                    foreach ($title->getMessages() as $message) {
                        $this->add(tr('Problem encountered!'), $message, 'warning', null, 5000);
                    }
                }

                return $this;
            }
        }

        $this->source[] = $title;
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

        foreach ($this->source as $message) {
            $this->render .= $message->renderBare();
        }

        // Add script tags around all the flash calls
        $this->render = Script::new()->setContent($this->render)->render();

        // Remove all flash messages from this object
        $this->clear();
        $this->has_rendered = true;

        return parent::render();
    }



    /**
     * Export the flash messages in this object to an array
     *
     * @return array
     */
    public function export(): array
    {
        $return = [];

        foreach ($this->source as $message) {
            $return[] = $message->export();
        }

        return $return;
    }



    /**
     * Import the flash messages in the specified array to this object
     *
     * @param array $source
     * @return void
     */
    public function import(array $source): void
    {
        foreach ($source as $message) {
            $this->add(FlashMessage::new()->import($message));
        }
    }
}
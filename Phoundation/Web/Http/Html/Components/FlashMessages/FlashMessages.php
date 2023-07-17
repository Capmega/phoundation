<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\FlashMessages;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\ElementsBlock;
use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Http\Html\Enums\DisplayMode;


/**
 * Class FlashMessages
 *
 * This class tracks HTML flash messages and can render each message and return HTML code.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class FlashMessages extends ElementsBlock implements IteratorInterface
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
     * Add a "Success!" flash message
     *
     * @param string|null $message
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addSuccessMessage(?string $message = null, string $icon = null, ?int $auto_close = 5): static
    {
        return $this->addMessage(tr('Success!'), $message, DisplayMode::success, $icon, $auto_close);
    }


    /**
     * Add a "Warning!" flash message
     *
     * @param string|null $message
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addWarningMessage(?string $message = null, string $icon = null, ?int $auto_close = 5): static
    {
        return $this->addMessage(tr('Warning!'), $message, DisplayMode::warning, $icon, $auto_close);
    }


    /**
     * Add a "Validation failed" flash message
     *
     * @param string|null $message
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addValidationFailedMessage(?string $message = null, string $icon = null, ?int $auto_close = 5): static
    {
        return $this->addMessage(tr('Validation failed!'), $message, DisplayMode::warning, $icon, $auto_close);
    }


    /**
     * Add an "Error!" flash message
     *
     * @param string|null $message
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addErrorMessage(?string $message = null, string $icon = null, ?int $auto_close = 5): static
    {
        return $this->addMessage(tr('Error!'), $message, DisplayMode::error, $icon, $auto_close);
    }


    /**
     * Add a "Notice!" flash message
     *
     * @param string|null $message
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addNoticeMessage(?string $message = null, string $icon = null, ?int $auto_close = 5): static
    {
        return $this->addMessage(tr('Notice'), $message, DisplayMode::notice, $icon, $auto_close);
    }


    /**
     * Add a flash message
     *
     * @param FlashMessage|Exception|string|null $title
     * @param string|null $message
     * @param DisplayMode|null $mode
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addMessage(FlashMessage|Exception|string|null $title, ?string $message = null, ?DisplayMode $mode = null, string $icon = null, ?int $auto_close = 5): static
    {
        if ($title) {
            if ($title instanceof ValidationFailedException) {
                // Title was specified as an exception, add each validation failure as a separate flash message
                if ($title->getData()) {
                    $count = 0;

                    foreach ($title->getData() as $message) {
                        if (!trim($message)) {
                            continue;
                        }

                        $count++;
                        $this->addMessage(tr('Information validation failure'), $message, DisplayMode::warning, null, 5000);
                    }

                    if (!$count) {
                        throw new OutOfBoundsException(tr('The specified Validation exception ":e" has no or empty messages in the exception data', [
                            ':e' => $title
                        ]));
                    }

                    return $this;
                }

            } elseif ($title instanceof Exception) {
                // Title was specified as an exception, add each validation failure as a separate flash
                // message
                if ($title->getMessages()) {
                    foreach ($title->getMessages() as $message) {
                        $this->addMessage(tr('Problem encountered!'), $message, DisplayMode::warning, null, 5000);
                    }

                    return $this;
                }
            }

            // Title was specified as a string, make it a flash message
            if (!$message) {
                throw new OutOfBoundsException(tr('No message specified for the flash message ":title"', [
                    ':title' => $title
                ]));
            }

            $title = FlashMessage::new()
                ->setAutoClose($auto_close)
                ->setMessage($message)
                ->setTitle($title)
                ->setMode($mode)
                ->setIcon($icon);

            $this->source[] = $title;
        }

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
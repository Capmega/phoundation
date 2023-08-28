<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\FlashMessages;

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\ElementsBlock;
use Phoundation\Web\Http\Html\Components\FlashMessages\Interfaces\FlashMessageInterface;
use Phoundation\Web\Http\Html\Components\FlashMessages\Interfaces\FlashMessagesInterface;
use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\Interfaces\DisplayModeInterface;
use Stringable;
use Throwable;


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
class FlashMessages extends ElementsBlock implements FlashMessagesInterface
{
    /**
     * This method will move all messages from the specified FlashMessages object here.
     *
     * @param FlashMessagesInterface|null $messages
     * @return static
     */
    public function pullMessagesFrom(?FlashMessagesInterface $messages): static
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
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addSuccessMessage(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 10000): static
    {
        return $this->addMessage($message, tr('Success!'), DisplayMode::success, $icon, $auto_close);
    }


    /**
     * Add a "Warning!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addWarningMessage(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 0): static
    {
        return $this->addMessage($message, tr('Warning'), DisplayMode::warning, $icon, $auto_close);
    }


    /**
     * Add a "Validation failed" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addValidationFailedMessage(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 10000): static
    {
        return $this->addMessage($message, tr('Validation failed'), DisplayMode::warning, $icon, $auto_close);
    }


    /**
     * Add an "Error!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addErrorMessage(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 0): static
    {
        return $this->addMessage($message, tr('Something went wrong'), DisplayMode::error, $icon, $auto_close);
    }


    /**
     * Add a "Notice!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addNoticeMessage(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 10000): static
    {
        return $this->addMessage($message, tr('Notice'), DisplayMode::notice, $icon, $auto_close);
    }


    /**
     * Add a flash message
     *
     * @param FlashMessageInterface|Exception|Stringable|string|null $message
     * @param string|null $title
     * @param DisplayModeInterface|null $mode
     * @param string|null $icon
     * @param int|null $auto_close
     * @return $this
     */
    public function addMessage(FlashMessageInterface|Exception|Stringable|string|null $message, ?string $title = null, ?DisplayModeInterface $mode = DisplayMode::error, string $icon = null, ?int $auto_close = 5000): static
    {
        if (!$message) {
            // Ignore empty messages
            return $this;
        }

        if ($message instanceof ValidationFailedException) {
            // Title was specified as an exception, add each validation failure as a separate flash message
            if (empty($title)) {
                $title = tr('Validation failed');
            }

            if ($message->getData()) {
                $count = 0;

                foreach ($message->getData() as $message) {
                    if (!trim($message)) {
                        continue;
                    }

                    $count++;
                    $this->addValidationFailedMessage($message);
                }

                if (!$count) {
                    throw new OutOfBoundsException(tr('The specified Validation exception ":e" has no or empty messages in the exception data', [
                        ':e' => $title
                    ]));
                }

                return $this;
            }

            $mode = DisplayMode::warning;

        } elseif ($message instanceof Exception) {
            // Title was specified as a Phoundation exception, add each validation failure as a separate flash
            // message
            if (empty($title)) {
                $title = tr('Error');
            }

            foreach ($message->getMessages() as $message) {
                $this->addErrorMessage($message, $title);
            }

            return $this;
        }

        if ($message instanceof Throwable) {
            // Title was specified as a PHP exception, add the exception message as flash message
            $message = $message->getMessage();

            if (empty($title)) {
                $title = tr('Error');
            }
        }

        if (!$title) {
            // Title is required tho
            throw new OutOfBoundsException(tr('No title specified for the flash message ":message"', [
                ':message' => $message
            ]));
        }

        if (!($message instanceof FlashMessageInterface)) {
            // The message was not specified as a flash message, treat it as a string and make a flash message out of it
            $message = FlashMessage::new()
                ->setAutoClose($auto_close)
                ->setMessage((string) $message)
                ->setTitle($title)
                ->setMode($mode)
                ->setIcon($icon);
        }

        $this->source[] = $message;

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

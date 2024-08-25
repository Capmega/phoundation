<?php

/**
 * Class FlashMessages
 *
 * This class tracks HTML flash messages and can render each message and return HTML code.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\FlashMessages;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessageInterface;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessagesInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Stringable;
use Throwable;


class FlashMessages extends ElementsBlock implements FlashMessagesInterface
{
    /**
     * Adds the specified source(s) to the internal source
     *
     * @param IteratorInterface|array|string|null $source
     * @param bool                                $clear_keys
     * @param bool                                $exception
     *
     * @return static
     */
    public function addSource(IteratorInterface|array|string|null $source, bool $clear_keys = false, bool $exception = true): static
    {
        parent::addSource($source, $clear_keys, $exception);

        // Clear the messages from the specified FlashMessages object
        return $source->clear();
    }


    /**
     * Add a "Success!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     *
     * @return static
     */
    public function addSuccess(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 10000): static
    {
        return $this->addMessage($message, tr('Success!'), EnumDisplayMode::success, $icon, $auto_close);
    }


    /**
     * Add a flash message
     *
     * @param FlashMessageInterface|Exception|Stringable|string|null $message
     * @param string|null                                            $title
     * @param EnumDisplayMode|null                                   $mode
     * @param string|null                                            $icon
     * @param int|null                                               $auto_close
     *
     * @return static
     */
    public function addMessage(FlashMessageInterface|Exception|Stringable|string|null $message, ?string $title = null, ?EnumDisplayMode $mode = EnumDisplayMode::error, string $icon = null, ?int $auto_close = 5000): static
    {
        if (!$message) {
            // Ignore empty messages
            return $this;
        }

        if ($message instanceof ValidationFailedException) {
            // Title was specified as an exception, add each validation failure as a separate flash message
            $title = trim((string) $title);

            if (empty($title)) {
                $title = tr('Validation failed');
            }

            if (str_starts_with($title, '(')) {
                // This message is prefixed with the class name. Remove the class name as we don't want to show this to
                // the end users.
                $title = trim(Strings::from($title, '('));
            }

            if ($message->getData()) {
                $count = 0;

                foreach ($message->getDataKey('failures') as $message) {
                    if (!trim($message)) {
                        continue;
                    }

                    $count++;
                    $this->addValidationFailed($message);
                }

                if (!$count) {
                    throw new OutOfBoundsException(tr('The specified Validation exception ":e" has no or empty messages in the exception data', [
                        ':e' => $title,
                    ]));
                }

                return $this;
            }

            $mode = EnumDisplayMode::warning;

        } elseif ($message instanceof Exception) {
            // Title was specified as a Phoundation exception, add each validation failure as a separate flash
            // message
            if (empty($title)) {
                $title = tr('Error');
            }

            foreach ($message->getMessages() as $message) {
                $this->addException($message, $title);
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
                ':message' => $message,
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
     * Add a "Validation failed" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     *
     * @return static
     */
    public function addValidationFailed(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 10000): static
    {
        return $this->addMessage($message, tr('Validation failed'), EnumDisplayMode::warning, $icon, $auto_close);
    }


    /**
     * Add an "Error!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     *
     * @return static
     */
    public function addException(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 0): static
    {
        return $this->addMessage($message, tr('Something went wrong'), EnumDisplayMode::error, $icon, $auto_close);
    }


    /**
     * Add a "Warning!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     *
     * @return static
     */
    public function addWarning(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 0): static
    {
        return $this->addMessage($message, tr('Warning'), EnumDisplayMode::warning, $icon, $auto_close);
    }


    /**
     * Add a "Notice!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     *
     * @return static
     */
    public function addNoticeMessage(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 10000): static
    {
        return $this->addMessage($message, tr('Notice'), EnumDisplayMode::notice, $icon, $auto_close);
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
        $this->render = Script::new()
            ->setContent($this->render)
            ->render();

        // Remove all flash messages from this object
        $this->clear();
        $this->has_rendered = true;

        return parent::render();
    }


    /**
     * Renders all flash messages for JSON replies
     *
     * @return array
     */
    public function renderJson(): array
    {
        $render = [];

        foreach ($this->source as $message) {
            $render[] = $message->renderBare();
        }

        // Remove all flash messages from this object
        $this->clear();
        $this->has_rendered = true;

        return $render;
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
     *
     * @return void
     */
    public function import(array $source): void
    {
        foreach ($source as $message) {
            $this->add(FlashMessage::new()->import($message));
        }
    }
}

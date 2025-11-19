<?php

/**
 * Class FlashMessages
 *
 * This class tracks HTML flash messages and can render each message and return HTML code.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\FlashMessages;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\PhoException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessageInterface;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessagesInterface;
use Phoundation\Web\Html\Enums\EnumAttachJavascript;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Stringable;
use Throwable;


class FlashMessages extends ElementsBlock implements FlashMessagesInterface
{
    /**
     * Tracks where the JavaScript for these flash messages should be attached to
     *
     * @var EnumAttachJavascript
     */
    protected EnumAttachJavascript $attach_javascript = EnumAttachJavascript::footer;

    /**
     * Return where the JavaScript for these flash messages should be attached to
     *
     * @return EnumAttachJavascript
     */
    public function getAttachJavaScript(): EnumAttachJavascript
    {
        return $this->attach_javascript;
    }


    /**
     * Sets where the JavaScript for these flash messages should be attached to
     *
     * @param EnumAttachJavascript $attach_javascript
     *
     * @return static
     */
    public function setAttachJavaScript(EnumAttachJavascript $attach_javascript): static
    {
        $this->attach_javascript = $attach_javascript;
        return $this;
    }


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
     * Add a flash message
     *
     * @param FlashMessageInterface|PhoException|Stringable|string|null $message
     * @param string|null                                               $title
     * @param EnumDisplayMode|null                                      $mode
     * @param string|null                                               $icon
     * @param int|null                                                  $auto_close
     * @param bool                                                      $make_incident
     *
     * @return static
     */
    public function addMessage(FlashMessageInterface|PhoException|Stringable|string|null $message, ?string $title = null, ?EnumDisplayMode $mode = EnumDisplayMode::error, ?string $icon = null, ?int $auto_close = 5000, bool $make_incident = true): static
    {
        if (!$message) {
            // Ignore empty messages
            return $this;
        }

        if ($message instanceof Throwable) {
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

                    if ($message->dataKeyExists('failures')) {
                        foreach ($message->getDataKey('failures') as $failure) {
                            $failure['message'] = trim((string) array_get_safe($failure, 'message'));

                            if (!$failure['message']) {
                                continue;
                            }

                            $count++;
                            $this->addValidationFailed($failure['message']);
                        }

                    } else {
                        foreach ($message->getData() as $message) {
                            if (!trim($message)) {
                                continue;
                            }

                            $count++;
                            $this->addValidationFailed($message);
                        }
                    }

                    if (!$count) {
                        throw new OutOfBoundsException(tr('The specified Validation exception ":e" has no or empty messages in the exception data', [
                            ':e' => $title,
                        ]));
                    }

                    return $this;
                }

                $mode = EnumDisplayMode::warning;

            } elseif ($message instanceof PhoException) {
                // Title was specified as a Phoundation exception, add each validation failure as a separate flash
                // message
                if ($message->isWarning()) {
                    // These are warnings
                    if (empty($title)) {
                        $title = tr('Warning');
                    }

                    return $this->addWarning($message->getMessage(), $title);
                }

            } else {
                // These are full on error exceptions or PHP exceptions
                return $this->addException(tr('Something did not function correctly on our side, please try again later or contact your IT department'));
            }

            $message = $message->getMessage();
        }

        if (!$title) {
            // Title is required tho
            throw new OutOfBoundsException(tr('No title specified for the flash message ":message"', [
                ':message' => $message,
            ]));
        }

        if (!($message instanceof FlashMessageInterface)) {
            // The message wasn't specified as a flash message, treat it as a string and make a flash message out of it
            $message = FlashMessage::new()
                                   ->setAutoClose($auto_close)
                                   ->setMessage((string) $message)
                                   ->setTitle($title)
                                   ->setMode($mode)
                                   ->setIcon($icon)
                                   ->setMakeIncident($make_incident);
        }

        $this->source[] = $message->setAttachJavaScript($this->attach_javascript);

        return $this;
    }


    /**
     * Add a "Success!" flash message
     *
     * @param FlashMessageInterface|PhoException|string|null $message
     * @param string|null                                    $icon
     * @param int|null                                       $auto_close
     * @param bool                                           $make_incident
     *
     * @return static
     */
    public function addSuccess(FlashMessageInterface|PhoException|string|null $message = null, ?string $icon = null, ?int $auto_close = 10000, bool $make_incident = false): static
    {
        return $this->addMessage($message, tr('Success!'), EnumDisplayMode::success, $icon, $auto_close, $make_incident);
    }


    /**
     * Add a "Validation failed" flash message
     *
     * @param FlashMessageInterface|PhoException|string|null $message
     * @param string|null                                    $icon
     * @param int|null                                       $auto_close
     * @param bool                                           $make_incident
     *
     * @return static
     */
    public function addValidationFailed(FlashMessageInterface|PhoException|string|null $message = null, ?string $icon = null, ?int $auto_close = 10000, bool $make_incident = true): static
    {
        return $this->addMessage($message, tr('Validation failed'), EnumDisplayMode::warning, $icon, $auto_close, $make_incident);
    }


    /**
     * Add an "Error!" flash message
     *
     * @param FlashMessageInterface|PhoException|string|null $message
     * @param string|null                                    $icon
     * @param int|null                                       $auto_close
     * @param bool                                           $make_incident
     *
     * @return static
     */
    public function addException(FlashMessageInterface|PhoException|string|null $message = null, ?string $icon = null, ?int $auto_close = 0, bool $make_incident = true): static
    {
        return $this->addMessage($message, tr('Something went wrong'), EnumDisplayMode::error, $icon, $auto_close, $make_incident);
    }


    /**
     * Add a "Warning!" flash message
     *
     * @param FlashMessageInterface|PhoException|string|null $message
     * @param string|null                                    $icon
     * @param int|null                                       $auto_close
     * @param bool                                           $make_incident
     *
     * @return static
     */
    public function addWarning(FlashMessageInterface|PhoException|string|null $message = null, ?string $icon = null, ?int $auto_close = 0, bool $make_incident = true): static
    {
        return $this->addMessage($message, tr('Warning'), EnumDisplayMode::warning, $icon, $auto_close, $make_incident);
    }


    /**
     * Add a "Notice!" flash message
     *
     * @param FlashMessageInterface|PhoException|string|null $message
     * @param string|null                                    $icon
     * @param int|null                                       $auto_close
     * @param bool                                           $make_incident
     *
     * @return static
     */
    public function addNoticeMessage(FlashMessageInterface|PhoException|string|null $message = null, ?string $icon = null, ?int $auto_close = 10000, bool $make_incident = false): static
    {
        return $this->addMessage($message, tr('Notice'), EnumDisplayMode::notice, $icon, $auto_close, $make_incident);
    }


    /**
     * Renders all flash messages
     *
     * @param EnumAttachJavascript $attach_javascript
     *
     * @return string|null
     */
    public function render(EnumAttachJavascript $attach_javascript = EnumAttachJavascript::footer): ?string
    {
        $return = parent::render();

        // Clear the flash messages object content so that it won't ever render again
        $this->clear();

        return $return;
    }


    /**
     * Renders all flash messages into an array and returns it
     *
     * @return array
     */
    public function renderArray(): array
    {
        $render = [];

        foreach ($this->source as $message) {
            $render[] = $message->renderArray();
        }

        // Remove all flash messages from this object
        $this->clear();
        $this->has_rendered = true;
Log::printr($render);
        return $render;
    }


    /**
     * Renders all flash messages into a JSON object and returns it
     *
     * @return ?string
     */
    public function renderJson(): ?string
    {
        return Json::encode($this->renderArray());
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

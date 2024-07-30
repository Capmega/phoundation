<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces;

use Phoundation\Exception\Exception;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Stringable;

interface FlashMessagesInterface extends ElementsBlockInterface
{
    /**
     * Add a "Success!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     *
     * @return $this
     */
    public function addSuccess(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 10000): static;


    /**
     * Add a "Warning!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     *
     * @return $this
     */
    public function addWarning(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 0): static;


    /**
     * Add a "Validation failed" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     *
     * @return $this
     */
    public function addValidationFailed(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 10000): static;


    /**
     * Add an "Error!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     *
     * @return $this
     */
    public function addException(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 0): static;


    /**
     * Add a "Notice!" flash message
     *
     * @param FlashMessageInterface|Exception|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     *
     * @return $this
     */
    public function addNoticeMessage(FlashMessageInterface|Exception|string|null $message = null, string $icon = null, ?int $auto_close = 10000): static;


    /**
     * Add a flash message
     *
     * @param FlashMessageInterface|Exception|Stringable|string|null $message
     * @param string|null                                            $title
     * @param EnumDisplayMode|null                                   $mode
     * @param string|null                                            $icon
     * @param int|null                                               $auto_close
     *
     * @return $this
     */
    public function addMessage(FlashMessageInterface|Exception|Stringable|string|null $message, ?string $title = null, ?EnumDisplayMode $mode = EnumDisplayMode::error, string $icon = null, ?int $auto_close = 5000): static;


    /**
     * Renders all flash messages
     *
     * @return string|null
     */
    public function render(): ?string;


    /**
     * Export the flash messages in this object to an array
     *
     * @return array
     */
    public function export(): array;


    /**
     * Import the flash messages in the specified array to this object
     *
     * @param array $source
     *
     * @return void
     */
    public function import(array $source): void;
}

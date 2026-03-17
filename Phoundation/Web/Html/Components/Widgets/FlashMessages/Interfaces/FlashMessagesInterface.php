<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces;

use Phoundation\Exception\PhoException;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Stringable;
use Throwable;

interface FlashMessagesInterface extends ElementsBlockInterface
{
    /**
     * Add a "Success!" flash message
     *
     * @param FlashMessageInterface|Throwable|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     * @param bool                                        $make_incident
     *
     * @return static
     */
    public function addSuccess(FlashMessageInterface|Throwable|string|null $message = null, ?string $icon = null, ?int $auto_close = 10000, bool $make_incident = false): static;


    /**
     * Add a "Warning!" flash message
     *
     * @param FlashMessageInterface|Throwable|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     * @param bool                                        $make_incident
     *
     * @return static
     */
    public function addWarning(FlashMessageInterface|Throwable|string|null $message = null, ?string $icon = null, ?int $auto_close = 0, bool $make_incident = true): static;


    /**
     * Add a "Validation failed" flash message
     *
     * @param FlashMessageInterface|Throwable|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     * @param bool                                        $make_incident
     *
     * @return static
     */
    public function addValidationFailed(FlashMessageInterface|Throwable|string|null $message = null, ?string $icon = null, ?int $auto_close = 10000, bool $make_incident = true): static;


    /**
     * Add an "Error!" flash message
     *
     * @param FlashMessageInterface|Throwable|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     * @param bool                                        $make_incident
     *
     * @return static
     */
    public function addException(FlashMessageInterface|Throwable|string|null $message = null, ?string $icon = null, ?int $auto_close = 0, bool $make_incident = true): static;


    /**
     * Add a "Notice!" flash message
     *
     * @param FlashMessageInterface|Throwable|string|null $message
     * @param string|null                                 $icon
     * @param int|null                                    $auto_close
     * @param bool                                        $make_incident
     *
     * @return static
     */
    public function addNoticeMessage(FlashMessageInterface|Throwable|string|null $message = null, ?string $icon = null, ?int $auto_close = 10000, bool $make_incident = false): static;


    /**
     * Add a flash message
     *
     * @param FlashMessageInterface|Throwable|Stringable|string|null $message
     * @param string|null                                            $title
     * @param EnumDisplayMode|null                                   $mode
     * @param string|null                                            $icon
     * @param int|null                                               $auto_close
     * @param bool                                                   $make_incident
     *
     * @return static
     */
    public function addMessage(FlashMessageInterface|Throwable|Stringable|string|null $message, ?string $title = null, ?EnumDisplayMode $mode = EnumDisplayMode::error, ?string $icon = null, ?int $auto_close = 5000, bool $make_incident = true): static;


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

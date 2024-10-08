<?php

declare(strict_types=1);

namespace Phoundation\Exception\Interfaces;

use Phoundation\Exception\Exception;
use Phoundation\Notifications\Interfaces\NotificationInterface;
use Throwable;

/**
 * Class Exception
 *
 * This is the most basic Phoundation exception class
 *
 * @author    Sven Olaf Oostenbrink
 * @copyright Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Exception
 */
interface ExceptionInterface extends Throwable
{
    /**
     * Return the exception-related data
     *
     * @return array
     */
    public function getData(): array;


    /**
     * Set the exception data
     *
     * @param mixed       $data
     * @param string|null $key
     *
     * @return static
     */
    public function addData(mixed $data, ?string $key = null): static;


    /**
     * Returns the exception messages
     *
     * @return array
     */
    public function getMessages(): array;


    /**
     * Returns the exception messages
     *
     * @param array $messages
     *
     * @return Exception
     */
    public function addMessages(array $messages): static;


    /**
     * Changes the exception messages list to the specified messages
     *
     * @param array $messages
     *
     * @return static
     */
    public function setMessages(array $messages): static;


    /**
     * Changes the exception message to the specified message
     *
     * @param string $message
     *
     * @return static
     */
    public function setMessage(string $message): static;


    /**
     * Returns the warning setting for this exception. If true, the exception message may be displayed completely
     *
     * @return bool True if this exception is a warning, false otherwise.
     */
    public function getWarning(): bool;


    /**
     * Set the exception code
     *
     * @param string|int|null $code
     *
     * @return Exception
     */
    public function setCode(string|int|null $code = null): static;


    /**
     * Sets that this exception is a warning. If an exception is a warning, its message may be displayed completely
     *
     * @note This method returns $this, allowing chaining
     *
     * @param bool $warning True if this exception is a warning, false if not
     *
     * @return Exception
     */
    public function setWarning(bool $warning): static;


    /**
     * Sets that this exception is a warning. If an exception is a warning, its message may be displayed completely
     *
     * @note This method returns $this, allowing chaining
     * @return Exception
     */
    public function makeWarning(): static;


    /**
     * Returns true if this exception is a warning or false if not
     *
     * @return bool
     */
    public function isWarning(): bool;


    /**
     * Return the complete backtrace starting from the first exception that was thrown
     *
     * @param string $filters
     * @param bool   $skip_own
     *
     * @return array
     */
    public function getCompleteTrace(string $filters = 'args', bool $skip_own = true): array;


    /**
     * Write this exception to the log file
     *
     * @return Exception
     */
    public function log(): static;


    /**
     * Returns a notification object for this exception
     *
     * @return NotificationInterface
     */
    public function getNotificationObject(): NotificationInterface;


    /**
     * Register this exception in the developer incidents log
     *
     * @return Exception
     */
    public function registerDeveloperIncident(): static;


    /**
     * Export this exception as an array
     *
     * @return array
     */
    public function exportArray(): array;


    /**
     * Export this exception as a Json string
     *
     * @return string
     */
    public function exportString(): string;


    /**
     * Returns the exception stack trace limited to everything after the execute_page() call
     *
     * This limited trace is useful to show a more relevant stack trace. Once script processing has begun, everything
     * before execute_page() is typically less relevant and only a distraction. This trace will clear that up
     *
     * @return array
     */
    public function getLimitedTrace(): array;


    /**
     * Returns the backtrace as a JSON string
     *
     * @return string
     */
    public function getTraceAsJson(): string;


    /**
     * Returns the backtrace as an array with nicely formatted lines
     *
     * @return array
     */
    public function getTraceAsFormattedArray(): array;


    /**
     * Returns the backtrace as a string with nicely formatted lines
     *
     * @return string
     */
    public function getTraceAsFormattedString(): string;


    /**
     * Generates and returns a full exception data array
     *
     * @return array
     */
    public function generateDetails(): array;
}

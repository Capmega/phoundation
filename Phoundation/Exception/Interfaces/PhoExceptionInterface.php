<?php

namespace Phoundation\Exception\Interfaces;

use PDOStatement;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\Interfaces\ArraySourceInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Interfaces\PoadInterface;
use Phoundation\Exception\PhoException;
use Phoundation\Exception\PhpException;
use Phoundation\Notifications\Interfaces\NotificationInterface;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Utils\Utils;
use Throwable;

interface PhoExceptionInterface extends PoadInterface
{
    /**
     * Returns the source data of this exception
     *
     * @return array
     */
    public function __toArray(): array;

    /**
     * Returns this exception object as a string
     *
     * @note Some (important!) information may be dropped, like the exception data
     * @return string
     */
    public function __toString(): string;

    /**
     * Returns true if a PhoException object has ever been created
     *
     * @return bool
     */
    public static function hasBeenCreated(): bool;

    /**
     * Changes the exception message to the specified message
     *
     * @param string $message
     *
     * @return static
     */
    public function setMessage(string $message): static;

    /**
     * Returns true if the exception message matches the specified needle(s)
     *
     * @param array|string $needle
     * @param bool         $case_insensitive
     *
     * @return bool
     */
    public function messageContains(array|string $needle, bool $case_insensitive = true): bool;

    /**
     * Returns the exception messages
     *
     * @return array
     */
    public function getMessages(): array;

    /**
     * Changes the exception messages list to the specified messages
     *
     * @param array $messages
     *
     * @return static
     */
    public function setMessages(array $messages): static;

    /**
     * Returns true if this exception has data attached
     *
     * @return bool
     */
    public function hasData(): bool;

    /**
     * Return the exception-related data
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Sets the specified data for this exception
     *
     * @param mixed $data
     *
     * @return static
     */
    public function setData(mixed $data): static;

    /**
     * Sets the line where the exception occurred
     *
     * @param int $line
     *
     * @return static
     */
    public function setLine(int $line): static;

    /**
     * Sets the file where the exception occurred
     *
     * @param string $file
     *
     * @return static
     */
    public function setFile(string $file): static;

    /**
     * Returns the exception messages
     *
     * @param array|string|null $messages
     *
     * @return static
     */
    public function addMessages(array|string|null $messages): static;

    /**
     * Set the exception code
     *
     * @param string|int|null $code
     *
     * @return static
     */
    public function setCode(string|int|null $code = null): static;

    /**
     * Returns the source data when cast to array in POA (Phoundation Object Array) format. This format allows any
     * object to be recreated from this array
     *
     * POA structures must have the following format
     * [
     *     "datatype" => The phoundation version that created this array
     *     "datatype" => "object"
     *     "class"    => The class name (static::class should suffice)
     *     "source"   => The object's source data
     * ]
     *
     * @return array
     */
    public function getPoadArray(): array;

    /**
     * Returns this exception object as an array
     *
     * @return array
     */
    public function getSource(): array;

    /**
     * Return the exception-related data
     *
     * @param string|int $key
     *
     * @return mixed
     */
    public function getDataKey(string|int $key): mixed;

    /**
     * Returns true if the specified exception data key exists
     *
     * @param string|int $key
     *
     * @return bool
     */
    public function dataKeyExists(string|int $key): bool;

    /**
     * Returns true if the exception data matches the specified needle(s)
     *
     * @param array|string $needle
     *
     * @return bool
     */
    public function dataContains(array|string $needle): bool;

    /**
     * Returns true if the exception data matches the specified needle(s)
     *
     * @param array|string          $needles
     * @param int                   $options
     * @param string|float|int|null $key
     *
     * @return bool
     */
    public function dataMatches(array|string $needles, int $options = Utils::MATCH_ALL | Utils::MATCH_CONTAINS | Utils::MATCH_CASE_INSENSITIVE, string|float|int|null $key = null): bool;

    /**
     * Returns exception data that matches the specified needle(s)
     *
     * @param array|string          $needles
     * @param int                   $options
     * @param string|float|int|null $key
     *
     * @return array
     */
    public function getDataMatch(array|string $needles, int $options = Utils::MATCH_ALL | Utils::MATCH_CONTAINS | Utils::MATCH_CASE_INSENSITIVE, string|float|int|null $key = null): array;

    /**
     * Returns true if the exception data matches the specified Perl compatible regular expression
     *
     * @param string $regex
     *
     * @return bool
     */
    public function dataMatchesRegex(string $regex): bool;

    /**
     * Returns the matches from the data with the Perl compatible regular expression
     *
     * @param string $regex
     *
     * @return array
     */
    public function getDataRegexMatches(string $regex): array;

    /**
     * Add relevant exception data
     *
     * @param mixed       $data
     * @param string|null $key
     *
     * @return static
     */
    public function addData(mixed $data, ?string $key = null): static;

    /**
     * Returns true if the exception message matches the specified needle(s)
     *
     * @param DataIteratorInterface|array|string|null $needles
     * @param int                                     $flags Flags that will modify this functions behavior.
     *
     * Supported match flags are:
     *
     * Utils::MATCH_CASE_INSENSITIVE  Will match needles for entries in case-insensitive mode.
     * Utils::MATCH_ALL               Will match needles for entries that contain all the specified needles.
     * Utils::MATCH_ANY               Will match needles for entries that contain any of the specified needles.
     * Utils::MATCH_BEGINS_WITH       Will match needles for entries that start with the specified needles. Mutually
     *                                exclusive with Utils::MATCH_ENDS_WITH, Utils::MATCH_CONTAINS,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_ENDS_WITH         Will match needles for entries that end with the specified needles. Mutually
     *                                exclusive with Utils::MATCH_BEGINS_WITH, Utils::MATCH_CONTAINS,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_CONTAINS          Will match needles for entries that contain the specified needles anywhere.
     *                                Mutually exclusive with Utils::MATCH_BEGINS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_RECURSE           Will recurse into arrays, if encountered.
     * Utils::MATCH_NOT               Will match needles for entries that do NOT match the needle.
     * Utils::MATCH_STRICT            Will match needles for entries that match the needle strict (so 0 does NOT match
     *                                "0", "" does NOT match 0, etc.).
     * Utils::MATCH_FULL              Will match needles for entries that fully match the needle. Mutually
     *                                exclusive with Utils::MATCH_BEGINS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_CONTAINS, and Utils::MATCH_REGEX.
     * Utils::MATCH_REGEX             Will match needles for entries that match the specified regular expression.
     *                                Mutually exclusive with Utils::MATCH_BEGINS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_CONTAINS, and Utils::MATCH_FULL.
     * Utils::MATCH_EMPTY             Will match empty values instead of ignoring them. NOTE: Empty values may be
     *                                ignored while NULL values are still matched using the MATCH_NULL flag
     * Utils::MATCH_NULL              Will match NULL values instead of ignoring them. NOTE: NULL values may be
     *                                ignored while non-NULL empty values are still matched using the MATCH_EMPTY flag
     * Utils::MATCH_REQUIRE           Requires at least one result
     * Utils::MATCH_SINGLE            Will match only a single entry for the executed action (return, remove, etc.)
     *
     * @return bool
     */
    public function messageMatches(DataIteratorInterface|array|string|null $needles, int $flags = Utils::MATCH_ALL | Utils::MATCH_CONTAINS | Utils::MATCH_CASE_INSENSITIVE): bool;

    /**
     * Sets that this exception is a warning. If an exception is a warning, its message may be displayed completely
     *
     * @note This method returns $this, allowing chaining
     * @return static
     */
    public function makeWarning(): static;

    /**
     * Returns true if this exception is a warning or false if not
     *
     * @return bool
     */
    public function isWarning(): bool;

    /**
     * Returns the warning setting for this exception. If true, the exception message may be displayed completely
     *
     * @return bool True if this exception is a warning, false otherwise.
     */
    public function getWarning(): bool;

    /**
     * Sets that this exception is a warning. If an exception is a warning, its message may be displayed completely
     *
     * @note This method returns $this, allowing chaining
     *
     * @param bool $warning True if this exception is a warning, false if not
     *
     * @return static
     */
    public function setWarning(bool $warning): static;

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
     * @return static
     */
    public function log(): static;

    /**
     * Returns a notification object for this exception
     *
     * @return NotificationInterface
     */
    public function getNotificationObject(): NotificationInterface;

    /**
     * Returns a new exception object
     *
     * @param Throwable|array|string|null $messages
     * @param Throwable|null              $previous
     *
     * @return static
     */
    public static function new(Throwable|array|string|null $messages, ?Throwable $previous = null): static;

    /**
     * Register this exception in the developer incidents log
     *
     * @param EnumSeverity|null $severity
     * @param string|null       $type
     *
     * @return static
     */
    public function registerIncident(?EnumSeverity $severity = null, ?string $type = null): static;

    /**
     * @param Throwable $e
     * @param string    $exception_class
     *
     * @return static
     */
    public static function ensurePhoundationException(Throwable $e, string $exception_class = PhpException::class): PhoException;

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
     * Returns the backtrace as a string with nicely formatted lines
     *
     * @param int $indent
     *
     * @return string
     */
    public function getTraceAsFormattedString(int $indent = 0): string;

    /**
     * Returns the backtrace as an array with nicely formatted lines
     *
     * @param int $indent
     *
     * @return array
     */
    public function getTraceAsFormattedArray(int $indent = 0): array;

    /**
     * Tracks if this exception has been logged
     *
     * @param null|bool $set
     *
     * @return bool
     */
    public function hasBeenLogged(?bool $set = null): bool;

    /**
     * Returns the list of possible fixes for this exception, if available
     *
     * @return array|null
     */
    public function getFixes(): ?array;

    /**
     * Adds a possible fix to this exception
     *
     * @param string $fix
     *
     * @return static
     */
    public function addFix(string $fix): static;

    /**
     * Adds a hint to possibly solve the exception to the exception
     *
     * @param array|string $hint
     *
     * @return static
     */
    public function addHint(array|string $hint): static;


    /**
     * Sets a hint to possibly solve the exception to the exception
     *
     * @param array|string $hint
     *
     * @return static
     */
    public function setHints(array|string $hint): static;


    /**
     * Returns any possible hints to possibly solve the exception that may have been added to the exception
     *
     * @return array|string|null
     */
    public function getHints(): array|string|null;

    /**
     * Returns true if this exception has onr or more hints attached
     *
     * @return bool
     */
    public function hasHints(): bool;
}

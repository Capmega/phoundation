<?php

/**
 * Class PhoException
 *
 * This is the most basic Phoundation Exception class, expanding the PHP Exception class with a variety of new functions
 *
 * This class can:
 *
 * Store multiple exception messages
 *
 * Attach data relevant to the exception in an array using Exception::setData(), Exception::addData()
 *
 * Register the exception as a security incident using Exception::registerIncident()
 *
 * Distinguish between normal exceptions and warning exceptions, the latter being logged in yellow and not displaying
 * backtrace information. Warning exceptions are usually used in cases where input data is invalid, like
 * ValidationFailedException, or NotFound type exceptions. Warning exceptions will have their message shown in flash
 * messages, and on the command line (for example) the warning would only display the message in yellow.
 *
 * Export the exception as an array or JSON string
 *
 * Import exported string or array exceptions into a new exception
 *
 * Return limited traced that start at the executed command or web page using Exception->getLimitedTrace()
 *
 * Automatically track if the exception has been logged by Log::exception()
 *
 * Convert PHP exceptions into Phoundation exceptions using Exception::ensurePhoundationException()
 *
 * Send the exception as a notification using Exception::getNotificationObject()
 *
 * Check if exception messages and or data matches specified needles
 *
 * @author    Sven Olaf Oostenbrink
 * @copyright Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Exception
 */


declare(strict_types=1);

namespace Phoundation\Exception;

use PDOStatement;
use Phoundation\Cli\CliAutoComplete;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\LogException;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\Enums\EnumPoadTypes;
use Phoundation\Data\Interfaces\ArraySourceInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Poad\Poad;
use Phoundation\Data\Traits\TraitMethodsPoad;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Exception\Interfaces\PhoExceptionInterface;
use Phoundation\Notifications\Interfaces\NotificationInterface;
use Phoundation\Notifications\Notification;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use RuntimeException;
use Throwable;


class PhoException extends RuntimeException implements PhoExceptionInterface
{
    use TraitMethodsPoad;


    /**
     * Exception data, if available
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Exception messages
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * Exception is warning or not
     *
     * @var bool $warning
     */
    protected bool $warning = false;

    /**
     * Exception code
     *
     * @note Not sure what datatype PHP has for this, but it doesn't allow datatype specification here
     * @var string $code
     */
    protected $code = 0;

    /**
     * The file where this exception occurred.
     *
     * @var string $file
     */
    protected string $file;

    /**
     * The line where this exception occurred.
     *
     * @var int $line
     */
    protected int $line;

    /**
     * Tracks if this exception has been logged or not
     *
     * @var bool $has_been_logged
     */
    protected bool $has_been_logged = false;

    /**
     * Tracks if one of these exceptions has ever been generated
     *
     * @var bool $has_been_created
     */
    protected static bool $has_been_created = false;


    /**
     * Exception constructor
     *
     * @param Throwable|array|string|null $messages The exception messages
     * @param Throwable|null              $previous A previous exception, if available.
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        static::$has_been_created = true;

        if (is_object($messages)) {
            // The message actually is an Exception! Extract data and make this exception the previous
            $previous = $messages;

            if ($messages instanceof PhoException) {
                // This is a Phoundation exception, get more information
                $message = $messages->getMessage();

                $this->setMessages($messages->getMessages());
                $this->setWarning($messages->getWarning());
                $this->setData($messages->getData());

            } else {
                // This is a standard PHP exception
                $message = $messages->getMessage();

                $this->setLine($messages->getLine());
                $this->setFile($messages->getFile());
            }

        } elseif (is_array($messages)) {
            if (empty($messages)) {
                $messages = tr('No message specified');
            }

            $message = array_shift($messages);
            $message = Strings::force($message);
            $message = trim($message);

            // Add all extra messages
            $this->addMessages($messages);

        } else {
            // Fix the location and backtrace due to Exception::new() usage?
            if (isset($this->file) and ($this->file === __FILE__)) {
                // Adjust the file and location pointers by 1, remove the top trace entry
                $trace = $this->getTrace();
                $this->setFile($trace[0]['file'])
                     ->setLine($trace[0]['line']);
            }

            if (!$messages) {
                $messages = tr('No message specified');
            }

            $message = $messages;
            $message = trim((string) $message);
        }

        // Log all exceptions EXCEPT LogExceptions as those can cause endless loops
        if (!$this instanceof LogException) {
            if (Debug::isEnabled()) {
                if (config()->getBoolean('debug.exceptions.log.auto.enabled', false)) {
                    if (config()->getBoolean('debug.exceptions.log.auto.full', true)) {
                        $this->message = $messages;
                        Log::error($this, 2);

                    } else {
                        Log::warning('Exception: ' . $message, 2, echo_screen: !CliAutoComplete::isActive());
                    }

                    $this->has_been_logged = false;

                    if ($previous instanceof PhoException) {
                        $previous->hasBeenLogged(false);
                    }
                }
            }
        }

        // Pass the warning flag and data along to this exception
        if ($previous instanceof PhoException) {
            $this->setWarning($this->getWarning() or $previous->getWarning());
            $this->addData($previous->getData());
        }

        parent::__construct($message, 0, $previous);
    }


    /**
     * Returns the source data of this exception
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->getPoadArray();
    }


    /**
     * Returns this exception object as a string
     *
     * @note Some (important!) information may be dropped, like the exception data
     * @return string
     */
    public function __toString(): string
    {
        return $this->getPoadString(true);
    }


    /**
     * Returns true if a PhoException object has ever been created
     *
     * @return bool
     */
    public static function hasBeenCreated(): bool
    {
        return static::$has_been_created;
    }


    /**
     * Changes the exception message to the specified message
     *
     * @param string $message
     *
     * @return static
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }


    /**
     * Returns true if the exception message matches the specified needle(s)
     *
     * @param array|string $needle
     * @param bool         $case_insensitive
     *
     * @return bool
     */
    public function messageContains(array|string $needle, bool $case_insensitive = true): bool
    {
        if ($case_insensitive) {
            $needle  = strtolower($needle);
            $message = strtolower($this->getMessage());

        } else {
            $message = $this->getMessage();
        }

        return str_contains($message, $needle);
    }


    /**
     * Returns the exception messages
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }


    /**
     * Changes the exception messages list to the specified messages
     *
     * @param array $messages
     *
     * @return static
     */
    public function setMessages(array $messages): static
    {
        $this->messages = $messages;

        return $this;
    }


    /**
     * Returns true if this exception has data attached
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return (bool) count($this->data);
    }


    /**
     * Return the exception-related data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }


    /**
     * Sets the specified data for this exception
     *
     * @param mixed $data
     *
     * @return static
     */
    public function setData(mixed $data): static
    {
        $this->data = Arrays::force($data, null);

        return $this;
    }


    /**
     * Sets the line where the exception occurred
     *
     * @param int $line
     *
     * @return static
     */
    public function setLine(int $line): static
    {
        $this->line = $line;

        return $this;
    }


    /**
     * Sets the file where the exception occurred
     *
     * @param string $file
     *
     * @return static
     */
    public function setFile(string $file): static
    {
        $this->file = $file;

        return $this;
    }


    /**
     * Returns the exception messages
     *
     * @param array|string|null $messages
     *
     * @return static
     */
    public function addMessages(array|string|null $messages): static
    {
        if ($messages) {
            if (is_array($messages)) {
                // Add multiple messages
                foreach ($messages as $message) {
                    $this->messages[] = trim($message);
                }

            } else {
                // Add a single message
                $this->messages[] = trim($messages);
            }
        }

        return $this;
    }


    /**
     * Import exception data and return this as an exception
     *
     * @param ArraySourceInterface|array|string|null $source
     *
     * @return static|null
     */
    public static function newFromSourceOrNull(ArraySourceInterface|array|string|null $source): ?static
    {
        if ($source === null) {
            // Nothing to import, there is no exception
            return null;
        }

        return static::newFromSource($source);
    }


    /**
     * Compatibility wrapper method
     *
     * @param DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source
     *
     * @return static
     */
    public static function newFromSourceDirect(DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source = null): static
    {
        return static::newFromSource($source);
    }


    /**
     * Import exception data and return this as an exception
     *
     * @param DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source
     *
     * @return static
     */
    public static function newFromSource(DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source = null): static
    {
        try {
            if (is_string($source)) {
                // Make it an exception array
                $source = Json::decode($source);
            }

            if (array_key_exists('poad', $source)) {
                // Do POAD import
                $return = Poad::new($source)->getObject();

                if ($return instanceof PhoException) {
                    return $return;
                }

                // The specified POAD doesn't contain a PhoException compatible object but something entirely different
                throw PhoException::new(tr('Failed to import exception object from POAD (Phoundation Object Array Data) source, decoded POAD data does not contain a valid exception object'))
                                  ->addData([
                                      'source'  => $source,
                                      'decoded' => $return
                                  ]);
            }

            if (array_get_safe($source,'previous')) {
                $previous = static::newFromSource(array_get_safe($source,'previous'));
            }

            $source['class'] = isset_get($source['class'], PhoException::class);

            // Import data, check for old Phoundation\Exception\Exception class!
            if ($source['class'] === 'Phoundation\Exception\Exception') {
                $source['class'] = PhoException::class;
            }

            $e = new $source['class']($source['message'], isset_get($previous));
            $e->setCode(isset_get($source['code']));
            $e->setData(isset_get($source['data']));
            $e->setWarning((bool) isset_get($source['warning']));
            $e->addMessages(isset_get($source['messages']));

            return $e;

        } catch (Throwable $e) {
            throw PhoException::new(tr('Failed to generate exception object from import data'), $e)
                              ->setData(['data' => $source]);
        }
    }


    /**
     * Set the exception code
     *
     * @param string|int|null $code
     *
     * @return static
     */
    public function setCode(string|int|null $code = null): static
    {
        $this->code = $code;
        return $this;
    }


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
    public function getPoadArray(): array
    {
        return Poad::generateArray($this->getSource(), static::class, EnumPoadTypes::object);
    }


    /**
     * Returns this exception object as an array
     *
     * @return array
     */
    public function getSource(): array
    {
        $return = [
            'code'     => $this->getCode(),
            'class'    => static::class,
            'message'  => $this->getMessage(),
            'messages' => $this->getMessages(),
            'data'     => $this->getData(),
            'file'     => $this->getFile(),
            'line'     => $this->getLine(),
            'trace'    => $this->getTrace(),
            'warning'  => $this->getWarning(),
        ];

        $previous = $this->getPrevious();

        if ($previous) {
            if ($previous instanceof PhoException) {
                $return['previous'] = $previous->__toArray();

            } else {
                // This is a standard PHP exception
                $return['previous'] = [
                    'code'     => $previous->getCode(),
                    'class'    => get_class($previous),
                    'message'  => $previous->getMessage(),
                    'file'     => $this->getFile(),
                    'line'     => $this->getLine(),
                    'trace'    => $this->getTrace(),
                ];
            }
        }

        return $return;
    }


    /**
     * Return the exception-related data
     *
     * @param string|int $key
     *
     * @return mixed
     */
    public function getDataKey(string|int $key): mixed
    {
        return isset_get($this->data[$key]);
    }


    /**
     * Returns true if the specified exception data key exists
     *
     * @param string|int $key
     *
     * @return bool
     */
    public function dataKeyExists(string|int $key): bool
    {
        return array_key_exists($key, $this->data);
    }


    /**
     * Returns true if the exception data matches the specified needle(s)
     *
     * @param array|string $needle
     *
     * @return bool
     */
    public function dataContains(array|string $needle): bool
    {
        return str_contains(Strings::force($this->data), $needle);
    }


    /**
     * Returns true if the exception data matches the specified needle(s)
     *
     * @param array|string          $needles
     * @param int                   $options
     * @param string|float|int|null $key
     *
     * @return bool
     */
    public function dataMatches(array|string $needles, int $options = Utils::MATCH_ALL | Utils::MATCH_CONTAINS | Utils::MATCH_CASE_INSENSITIVE, string|float|int|null $key = null): bool
    {
        return (bool) $this->getDataMatch($needles, $options, $key);
    }


    /**
     * Returns exception data that matches the specified needle(s)
     *
     * @param array|string          $needles
     * @param int                   $options
     * @param string|float|int|null $key
     *
     * @return array
     */
    public function getDataMatch(array|string $needles, int $options = Utils::MATCH_ALL | Utils::MATCH_CONTAINS | Utils::MATCH_CASE_INSENSITIVE, string|float|int|null $key = null): array
    {
        if ($key) {
            return Arrays::keepMatchingValues(isset_get($this->data[$key], []), $needles, $options);
        }

        return Arrays::keepMatchingValues($this->data, $needles, $options);
    }


    /**
     * Returns true if the exception data matches the specified Perl compatible regular expression
     *
     * @param string $regex
     *
     * @return bool
     */
    public function dataMatchesRegex(string $regex): bool
    {
        return (bool) preg_match($regex, Strings::force($this->data, "\n"));
    }


    /**
     * Returns the matches from the data with the Perl compatible regular expression
     *
     * @param string $regex
     *
     * @return array
     */
    public function getDataRegexMatches(string $regex): array
    {
        preg_match_all($regex, Strings::force($this->data, "\n"), $matches);
        return $matches;
    }


    /**
     * Add relevant exception data
     *
     * @param mixed       $data
     * @param string|null $key
     *
     * @return static
     */
    public function addData(mixed $data, ?string $key = null): static
    {
        if (is_array($data) and ($key === null)) {
            // Add this exception data to the existing data
            $this->data = array_merge($this->data, $data);

        } else {
            if ($key === null) {
                $key = Strings::getRandom();
            }
            $this->data[$key] = $data;
        }

        return $this;
    }


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
     * Utils::MATCH_STARTS_WITH       Will match needles for entries that start with the specified needles. Mutually
     *                                exclusive with Utils::MATCH_ENDS_WITH, Utils::MATCH_CONTAINS,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_ENDS_WITH         Will match needles for entries that end with the specified needles. Mutually
     *                                exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_CONTAINS,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_CONTAINS          Will match needles for entries that contain the specified needles anywhere.
     *                                Mutually exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_RECURSE           Will recurse into arrays, if encountered.
     * Utils::MATCH_NOT               Will match needles for entries that do NOT match the needle.
     * Utils::MATCH_STRICT            Will match needles for entries that match the needle strict (so 0 does NOT match
     *                                "0", "" does NOT match 0, etc.).
     * Utils::MATCH_FULL              Will match needles for entries that fully match the needle. Mutually
     *                                exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_CONTAINS, and Utils::MATCH_REGEX.
     * Utils::MATCH_REGEX             Will match needles for entries that match the specified regular expression.
     *                                Mutually exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
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
    public function messageMatches(DataIteratorInterface|array|string|null $needles, int $flags = Utils::MATCH_ALL | Utils::MATCH_CONTAINS | Utils::MATCH_CASE_INSENSITIVE): bool
    {
        return Strings::matches($this->message, $needles, $flags);
    }


    /**
     * Sets that this exception is a warning. If an exception is a warning, its message may be displayed completely
     *
     * @note This method returns $this, allowing chaining
     * @return static
     */
    public function makeWarning(): static
    {
        $this->setWarning(true);

        // Make all previous exceptions warnings too, if possible
        $e = $this->getPrevious();

        if ($e instanceof PhoException) {
            $e->makeWarning();
        }

        return $this;
    }


    /**
     * Returns true if this exception is a warning or false if not
     *
     * @return bool
     */
    public function isWarning(): bool
    {
        return $this->getWarning();
    }


    /**
     * Returns the warning setting for this exception. If true, the exception message may be displayed completely
     *
     * @return bool True if this exception is a warning, false otherwise.
     */
    public function getWarning(): bool
    {
        if (defined('NOWARNINGS') and NOWARNINGS) {
            // No warnings allowed by the environment configuration
            return false;
        }

        return $this->warning;
    }


    /**
     * Sets that this exception is a warning. If an exception is a warning, its message may be displayed completely
     *
     * @note This method returns $this, allowing chaining
     *
     * @param bool $warning True if this exception is a warning, false if not
     *
     * @return static
     */
    public function setWarning(bool $warning): static
    {
        if (defined('NOWARNINGS') and NOWARNINGS) {
            // No warnings allowed by the environment configuration
            $warning = false;
        }

        if (!Core::inBootState() and !config()->getBoolean('debug.exceptions.warnings', true)) {
            // No warnings allowed from the configuration
            $warning = false;
        }

        $this->warning = $warning;

        return $this;
    }


    /**
     * Return the complete backtrace starting from the first exception that was thrown
     *
     * @param string $filters
     * @param bool   $skip_own
     *
     * @return array
     */
    public function getCompleteTrace(string $filters = 'args', bool $skip_own = true): array
    {
        $e = $this;

        while ($e->getPrevious()) {
            $e = $e->getPrevious();
        }

        $filters = Arrays::force($filters);
        $trace   = [];

        foreach ($e->getTrace() as $key => $value) {
            if ($skip_own and ($key <= 1)) {
                continue;
            }

            foreach ($filters as $filter) {
                unset($value[$filter]);
            }

            $trace[] = $value;
        }

        return $trace;
    }


    /**
     * Write this exception to the log file
     *
     * @return static
     */
    public function log(): static
    {
        if ($this->warning) {
            Log::warning($this);

        } else {
            Log::error($this);
        }

        return $this;
    }


    /**
     * Returns a notification object for this exception
     *
     * @return NotificationInterface
     */
    public function getNotificationObject(): NotificationInterface
    {
        return Notification::new()
                           ->setException($this);
    }


    /**
     * Returns a new exception object
     *
     * @param Throwable|array|string|null $messages
     * @param Throwable|null              $previous
     *
     * @return static
     */
    public static function new(Throwable|array|string|null $messages, ?Throwable $previous = null): static
    {
        return new static($messages, $previous);
    }


    /**
     * Register this exception in the developer incidents log
     *
     * @param EnumSeverity|null $severity
     * @param string|null       $type
     *
     * @return static
     */
    public function registerIncident(?EnumSeverity $severity = null, ?string $type = null): static
    {
        $incident = Incident::new()->setException($this);

        if ($type) {
            $incident->setType($type);
        }

        if ($severity) {
            $incident->setSeverity($severity);
        }

        $incident->save();

        return $this;
    }


    /**
     * @param Throwable $e
     * @param string    $exception_class
     *
     * @return static
     */
    public static function ensurePhoundationException(Throwable $e, string $exception_class = PhpException::class): PhoException
    {
        if ($e instanceof PhoException) {
            return $e;
        }

        $e = new $exception_class($e);

        if ($e instanceof PhoException) {
            return $e;
        }

        throw new OutOfBoundsException(tr('Cannot ensure exception is specified Phoundation exception class ":class" because the specified class should have the PhoException', [
            ':class' => $exception_class,
        ]), $e);
    }


    /**
     * Returns the exception stack trace limited to everything after the execute_page() call
     *
     * This limited trace is useful to show a more relevant stack trace. Once script processing has begun, everything
     * before execute_page() is typically less relevant and only a distraction. This trace will clear that up
     *
     * @return array
     */
    public function getLimitedTrace(): array
    {
        $trace = parent::getTrace();
        $next  = false;

        foreach ($trace as $key => $value) {
            if ($next) {
                unset($trace[$key]);

                return $trace;
            }

            if (isset_get($value['function']) === 'execute_page') {
                $next = true;
            }

            unset($trace[$key]);
        }

        return $trace;
    }


    /**
     * Returns the backtrace as a JSON string
     *
     * @return string
     */
    public function getTraceAsJson(): string
    {
        return Json::encode($this->getTrace());
    }


    /**
     * Returns the backtrace as a string with nicely formatted lines
     *
     * @param int $indent
     *
     * @return string
     */
    public function getTraceAsFormattedString(int $indent = 0): string
    {
        return implode(PHP_EOL, static::getTraceAsFormattedArray($indent));
    }


    /**
     * Returns the backtrace as an array with nicely formatted lines
     *
     * @param int $indent
     *
     * @return array
     */
    public function getTraceAsFormattedArray(int $indent = 0): array
    {
        return Debug::formatBackTrace($this->getTrace(), $indent);
    }


    /**
     * Tracks if this exception has been logged
     *
     * @param null|bool $set
     *
     * @return bool
     */
    public function hasBeenLogged(?bool $set = null): bool
    {
        if ($set !== null) {
            $this->has_been_logged = $set;
        }

        return $this->has_been_logged;
    }


    /**
     * Returns the list of possible fixes for this exception, if available
     *
     * @return array|null
     */
    public function getFixes(): ?array
    {
        return isset_get($this->data['fixes']);
    }


    /**
     * Adds a possible fix to this exception
     *
     * @param string $fix
     *
     * @return static
     */
    public function addFix(string $fix): static
    {
        if (empty($this->data['fixes'])) {
            $this->data['fixes'] = [];

        } elseif (!is_array($this->data['fixes'])) {
            Log::warning(ts('Exception data "fixes" is not an array, but contains data below instead. forcing array'));
            Log::printr($this->data['fixes']);

            $this->data['fixes'] = [];
        }

        $this->data['fixes'][] = $fix;
        return $this;
    }
}

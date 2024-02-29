<?php

declare(strict_types=1);

namespace Phoundation\Exception;

use Phoundation\Cli\CliAutoComplete;
use Phoundation\Cli\CliCommand;
use Phoundation\Core\Core;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Libraries\Version;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Developer\Debug;
use Phoundation\Developer\Incidents\Incident;
use Phoundation\Exception\Interfaces\ExceptionInterface;
use Phoundation\Notifications\Interfaces\NotificationInterface;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use Phoundation\Web\Page;
use Phoundation\Web\Routing\Route;
use RuntimeException;
use Throwable;


/**
 * Class Exception
 *
 * This is the most basic Phoundation exception class
 *
 * @author Sven Olaf Oostenbrink
 * @copyright Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Exception
 */
class Exception extends RuntimeException implements Interfaces\ExceptionInterface
{
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
     * Exception constructor
     *
     * @param Throwable|array|string|null $messages The exception messages
     * @param Throwable|null $previous A previous exception, if available.
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        if (is_object($messages)) {
            // The message actually is an Exception! Extract data and make this exception the previous
            $previous = $messages;

            if ($messages instanceof ExceptionInterface) {
                // This is a Phoundation exception, get more information
                $this->setMessage($messages->getMessage());
                $this->setMessages($messages->getMessages());
                $this->setWarning($messages->getWarning());
                $this->setData($messages->getData());

                $messages = $messages->getMessages();

            } else {
                // This is a standard PHP exception
                $this->setLine($messages->getLine());
                $this->setFile($messages->getFile());

                $messages = [$messages->getMessage()];
            }

        } elseif (!is_array($messages)) {
            if (!$messages) {
                $messages = tr('No message specified');
            }

            $messages = [$messages];
        }

        // Fix the location and backtrace due to Exception::new() usage?
        if (isset($this->file) and ($this->file === __FILE__)) {
            // Adjust the file and location pointers by 1, remove the top trace entry
            $trace = $this->getTrace();
            $this->setFile($trace[0]['file'])->setLine($trace[0]['line']);
        }

        $message = reset($messages);
        $message = Strings::force($message);
        $message = trim($message);

        Log::warning('Exception: ' . $message, 2, echo_screen: !CliAutoComplete::isActive());

        // Remove the first message from $messages as this is stored in static::getMessage()
        array_shift($messages);

        $this->addMessages($messages);
        parent::__construct($message, 0, $previous);
// print_r($this); die();
    }


    /**
     * Returns this exception object as a string
     *
     * @note Some (important!) information may be dropped, like the exception data
     * @return string
     */
    public function __toString(): string
    {
        parent::__toString();
        return '[ ' . ($this->warning ? 'WARNING ' : '') . $this->getCode() . ' ] ' . $this->getMessage();
    }


    /**
     * Returns a new exception object
     *
     * @param Throwable|array|string|null $messages
     * @param Throwable|null $previous
     * @return static
     */
    public static function new(Throwable|array|string|null $messages, ?Throwable $previous = null): static
    {
        return new static($messages, $previous);
    }


    /**
     * @param Throwable $e
     * @param string $exception_class
     * @return static
     */
    public static function ensurePhoundationException(Throwable $e, string $exception_class = PhpException::class): ExceptionInterface
    {
        if ($e instanceof ExceptionInterface) {
            return $e;
        }

        $e = new $exception_class($e);

        if ($e instanceof ExceptionInterface) {
            return $e;
        }

        throw new OutOfBoundsException(tr('Cannot ensure exception is specified Phoundation exception class ":class" because the specified class should have the ExceptionInterface', [
            ':class' => $exception_class
        ]), $e);
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
     * Return the exception-related data
     *
     * @param string|int $key
     * @return mixed
     */
    public function getDataKey(string|int $key): mixed
    {
        return isset_get($this->data[$key]);
    }


    /**
     * Returns exception data that matches the specified needle(s)
     *
     * @param array|string $needles
     * @param int $options
     * @param string|float|int|null $key
     * @return array
     */
    public function getDataMatch(array|string $needles, int $options = Utils::MATCH_ALL|Utils::MATCH_ANYWHERE|Utils::MATCH_NO_CASE, string|float|int|null $key = null): array
    {
        if ($key) {
            return Arrays::getMatches(isset_get($this->data[$key], []), $needles, $options);
        }

        return Arrays::getMatches($this->data, $needles, $options);
    }


    /**
     * Returns true if the exception data matches the specified needle(s)
     *
     * @param array|string $needles
     * @param int $options
     * @param string|float|int|null $key
     * @return bool
     */
    public function dataContains(array|string $needles, int $options = Utils::MATCH_ALL|Utils::MATCH_ANYWHERE|Utils::MATCH_NO_CASE, string|float|int|null $key = null): bool
    {
        return (bool) $this->getDataMatch($needles, $options, $key);
    }


    /**
     * Sets the specified data for this exception
     *
     * @param mixed $data
     * @return $this
     */
    public function setData(mixed $data): static
    {
        $this->data = Arrays::force($data);
        return $this;
    }


    /**
     * Add relevant exception data
     *
     * @param mixed $data
     * @param string|null $key
     * @return static
     */
    public function addData(mixed $data, ?string $key = null): static
    {
        if (is_array($data) and ($key === null)) {
            // Add this exception data to the existing data
            $this->data = array_merge($this->data, $data);

        } else {
            if ($key === null) {
                $key = Strings::randomSafe();
            }

            $this->data[$key] = $data;
        }

        return $this;
    }


    /**
     * Returns true if the exception message matches the specified needle(s)
     *
     * @param array|string $needles
     * @param int $options
     * @return bool
     */
    public function messageContains(array|string $needles, int $options = Utils::MATCH_ALL | Utils::MATCH_ANYWHERE| Utils::MATCH_NO_CASE): bool
    {
        return Strings::matches($this->message, $needles, $options);
    }


    /**
     * Returns true if the exception message matches the specified needle(s)
     *
     * @param array|string $needles
     * @param int $options
     * @return bool
     */
    public function messagesContain(array|string $needles, int $options = Utils::MATCH_ALL | Utils::MATCH_ANYWHERE| Utils::MATCH_NO_CASE): bool
    {
        return Arrays::matches($this->messages, $needles, $options);
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
     * Returns the exception messages
     *
     * @param array $messages
     * @return static
     */
    public function addMessages(array $messages): static
    {
        foreach ($messages as $message) {
            $this->messages[] = trim($message);
        }

        return $this;
    }


    /**
     * Changes the exception messages list to the specified messages
     *
     * @param array $messages
     * @return static
     */
    public function setMessages(array $messages): static
    {
        $this->messages = $messages;
        return $this;
    }


    /**
     * Changes the exception message to the specified message
     *
     * @param string $message
     * @return static
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }


    /**
     * Returns the warning setting for this exception. If true, the exception message may be displayed completely
     *
     * @return bool True if this exception is a warning, false otherwise.
     */
    public function getWarning(): bool
    {
        return $this->warning;
    }


    /**
     * Set the exception code
     *
     * @param string|int|null $code
     * @return static
     */
    public function setCode(string|int|null $code = null): static
    {
        $this->code = $code;
        return $this;
    }


    /**
     * Sets that this exception is a warning. If an exception is a warning, its message may be displayed completely
     *
     * @note This method returns $this, allowing chaining
     * @param bool $warning True if this exception is a warning, false if not
     * @return static
     */
    public function setWarning(bool $warning): static
    {
        if (defined('NOWARNINGS') and NOWARNINGS) {
            // No warnings allowed from the environment
            $warning = false;
        }

        if (!Config::getBoolean('debug.exceptions.warnings', true)) {
            // No warnings allowed from the configuration
            $warning = false;
        }

        $this->warning = $warning;
        return $this;
    }


    /**
     * Sets that this exception is a warning. If an exception is a warning, its message may be displayed completely
     *
     * @note This method returns $this, allowing chaining
     * @return static
     */
    public function makeWarning(): static
    {
        return $this->setWarning(true);
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
     * Return the complete backtrace starting from the first exception that was thrown
     *
     * @param string $filters
     * @param bool $skip_own
     * @return array
     */
    public function getCompleteTrace(string $filters = 'args', bool $skip_own = true): array
    {
        $e = $this;

        while ($e->getPrevious()) {
            $e = $e->getPrevious();
        }

        $filters = Arrays::force($filters);
        $trace = [];

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
        return Notification::new()->setException($this);
    }


    /**
     * Register this exception in the developer incidents log
     *
     * @return static
     */
    public function registerDeveloperIncident(): static
    {
        Incident::new()
            ->setException($this)
            ->setUrl(PLATFORM_WEB ? Route::getRequest() : CliCommand::getRequest())
            ->setType('exception')
            ->setData($this->generateDetails())
            ->save();

        return $this;
    }


    /**
     * Import exception data and return this as an exception
     *
     * @param array|string|null $source
     * @return static|null
     */
    public static function import(array|string|null $source): ?static
    {
        if ($source === null) {
            // Nothing to import, there is no exception
            return null;
        }

        if (is_string($source)) {
            // Make it an exception array
            $source = Json::decode($source);
        }

        $source['class'] = isset_get($source['class'], Exception::class);

        // Import data
        $e = new $source['class']($source['message']);
        $e->setCode(isset_get($source['code']));
        $e->setData(isset_get($source['data']));
        $e->setWarning((bool) isset_get($source['warning']));
        $e->addMessages(isset_get($source['messages']));

        return $e;
    }


    /**
     * Export this exception as an array
     *
     * @return array
     */
    public function exportArray(): array
    {
        return [
            'class'    => get_class($this),
            'code'     => $this->getCode(),
            'message'  => $this->getMessage(),
            'messages' => $this->getMessages(),
            'data'     => $this->getData(),
            'warning'  => $this->getWarning(),
        ];
    }


    /**
     * Export this exception as a Json string
     *
     * @return string
     */
    public function exportString(): string
    {
        $return = $this->exportArray();
        $return = Json::encode($return);

        return $return;
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
     * Sets the file where the exception occurred
     *
     * @param string $file
     * @return $this
     */
    public function setFile(string $file): static
    {
        $this->file = $file;
        return $this;
    }


    /**
     * Sets the line where the exception occurred
     *
     * @param int $line
     * @return $this
     */
    public function setLine(int $line): static
    {
        $this->line = $line;
        return $this;
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
     * Returns the backtrace as an array with nicely formatted lines
     *
     * @return array
     */
    public function getTraceAsFormattedArray(): array
    {
        return Debug::formatBackTrace($this->getTrace());
    }


    /**
     * Returns the backtrace as a string with nicely formatted lines
     *
     * @return string
     */
    public function getTraceAsFormattedString(): string
    {
        return implode(PHP_EOL, static::getTraceAsFormattedArray());
    }


    /**
     * Generates and returns a full exception data array
     *
     * @return array
     */
    public function generateDetails(): array
    {
        try {
            return [
                'project'               => PROJECT,
                'project_version'       => Core::getProjectVersion(),
                'database_version'      => Version::getString(Libraries::getMaximumVersion()),
                'environment'           => ENVIRONMENT,
                'platform'              => PLATFORM,
                'session'               => Session::getUUID(),
                'user'                  => Session::getUser()->getLogId(),
                'command'               => PLATFORM_CLI ? CliCommand::getCommandsString() : null,
                'url'                   => PLATFORM_WEB ? Route::getRequest()             : null,
                'method'                => PLATFORM_WEB ? Route::getMethod()              : null,
                'environment_variables' => $_ENV,
                'argv'                  => ArgvValidator::getBackupSource(),
                'get'                   => GetValidator::new()->getSource(),
                'post'                  => PostValidator::new()->getSource(),
            ];
        } catch (Throwable $e) {
            $e = static::ensurePhoundationException($e);

            return [
                'oops'               => 'Failed to generate exception details, see section "generate_exception"',
                'project'            => PROJECT,
                'project_version'    => Core::getProjectVersion(),
                'environment'        => ENVIRONMENT,
                'platform'           => PLATFORM,
                'generate_exception' => [
                    'message'  => $e->getMessage(),
                    'messages' => $e->getMessages(),
                    'trace'    => $e->getTrace(),
                    'data'     => $e->getData(),
                ]
            ];
        }
    }
}

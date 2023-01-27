<?php

namespace Phoundation\Exception;

use Phoundation\Core\Arrays;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Incidents\Incident;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Json;
use RuntimeException;
use Throwable;


/**
 * Class Exception
 *
 * This is the most basic Phoundation exception class
 *
 * @author Sven Olaf Oostenbrink
 * @copyright Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Exception
 */
class Exception extends RuntimeException
{
    /**
     * Exception data, if available
     *
     * @var mixed
     */
    protected mixed $data = null;

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
     * @var string $code
     */
    protected $code = 0;



    /**
     * CoreException __constructor
     *
     * @param Throwable|array|string|null $messages The exception messages
     * @param array $data [array] Data related to the exception. Should be a named array with elements that may be
     *      anything, string, array, object, resource, etc. The handler for this exception is assumed to know how to
     *      handle this data if it wants to do so
     * @param string|null $code The exception code (optional)
     * @param Throwable|null $previous A previous exception, if available.
     */
    public function __construct(Throwable|array|string|null $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null)
    {
        if (is_object($messages)) {
            // The message actually is an Exception! Extract data and make this exception the previous
            $previous = $messages;
            $code     = $messages->getCode();

            if ($messages instanceof Exception) {
                // This is a Phoundation exception, get more information
                $data     = $messages->getData();
                $messages = $messages->getMessages();
            } else {
                // This is a standard PHP exception
                $data     = null;
                $messages = [$messages->getMessage()];
            }
        } elseif (!is_array($messages)) {
            if (!$messages) {
                $messages = tr('No message specified');
            }

            $messages = [$messages];
        }

        $message = reset($messages);
        $message = Strings::force($message);

        $this->setCode($code);
        $this->setData($data);
        $this->addMessages($messages);

        parent::__construct($message, 0, $previous);
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
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return static
     */
    public static function new(Throwable|array|string|null $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): static
    {
        return new static($messages, $data, $code, $previous);
    }



    /**
     * Return the exception related data
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }



    /**
     * Set the exception data
     *
     * @param mixed $data
     * @return CoreException $this
     */
    public function setData(mixed $data): Exception
    {
        $this->data = $data;
        return $this;
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
     * @return Exception
     */
    public function addMessages(array $messages): Exception
    {
        foreach ($messages as $message) {
            $this->messages[] = $message;
        }

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
     * @param mixed $code
     */
    public function setCode(?string $code = null): Exception
    {
        $this->code = $code;
        return $this;
    }



    /**
     * Sets that this exception is a warning. If an exception is a warning, its message may be displayed completely
     *
     * @note This method returns $this, allowing chaining
     * @param bool $warning True if this exception is a warning, false if not
     * @return Exception
     */
    public function setWarning(bool $warning): Exception
    {
        if (defined('NOWARNINGS') and NOWARNINGS) {
            $warning = false;
        }

        $this->warning = $warning;
        return $this;
    }



    /**
     * Sets that this exception is a warning. If an exception is a warning, its message may be displayed completely
     *
     * @note This method returns $this, allowing chaining
     * @return Exception
     */
    public function makeWarning(): Exception
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
     * @return Exception
     */
    public function log(): static
    {
        Log::warning($this);
        return $this;
    }



    /**
     * Returns a notification object for this exception
     *
     * @return Notification
     */
    public function notification(): Notification
    {
        return Notification::new()->setException($this);
    }



    /**
     * Register this exception in the developer incidents log
     *
     * @return Exception
     */
    public function register(): static
    {
        Incident::new()->setException($this)->save();

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
}

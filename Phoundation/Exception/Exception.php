<?php

namespace Phoundation\Exception;

use MongoDB\Exception\RuntimeException;
use Phoundation\Core\Arrays;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Strings;
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
     * @param array|string $messages The exception messages
     * @param array $data [array] Data related to the exception. Should be a named array with elements that may be
     *      anything, string, array, object, resource, etc. The handler for this exception is assumed to know how to
     *      handle this data if it wants to do so
     * @param string|null $code The exception code (optional)
     * @param Throwable|null $previous A previous exception, if available.
     */
    public function __construct(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null)
    {
        if (!is_array($messages)) {
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
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return static
     */
    public static function new(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): static
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
}

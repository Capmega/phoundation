<?php

/**
 * Class FunctionCall
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */

namespace Phoundation\Developer;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Interfaces\FunctionCallInterface;
use Phoundation\Exception\OutOfBoundsException;

class FunctionCall implements FunctionCallInterface
{
    /**
     *
     *
     * @var string|null $class
     */
    protected ?string $class = null;

    /**
     *
     *
     * @var string|null $function
     */
    protected ?string $function;

    /**
     *
     *
     * @var string|null $file
     */
    protected ?string $file;

    /**
     *
     *
     * @var int|null $line
     */
    protected ?int $line;

    /**
     * @var IteratorInterface $arguments
     */
    protected IteratorInterface $arguments;

    /**
     * Backtrace cache
     *
     * @var array|null $backtrace
     */
    protected static ?array $backtrace;


    /**
     * FunctionCall class constructor
     *
     * @param int $offset
     * @param bool $cache
     */
    public function __construct(int $offset = 0, bool $cache = false)
    {
        if (empty(static::$backtrace) or !$cache) {
            static::$backtrace = debug_backtrace();
        }

        $offset++;

        if (!array_key_exists($offset, static::$backtrace)) {
            throw new OutOfBoundsException(tr('Requested offset is outside of call scope'));
        }

        $trace = static::$backtrace[$offset];

        $this->file      = isset_get($trace['file']);
        $this->line      = isset_get($trace['line'], -1);
        $this->function  = isset_get($trace['function']);
        $this->class     = isset_get($trace['class']);
        $this->arguments = new Iterator(isset_get($trace['args']));
    }


    /**
     * Returns the call location as a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getLocation();
    }


    public static function clearCache(): void
    {
        static::$backtrace = null;
    }

    /**
     * Returns the function (or method) of this call
     *
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }


    /**
     * Returns the file where this function is located
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }


    /**
     * Returns the line where this function is located
     *
     * @return int|null
     */
    public function getLine(): ?int
    {
        return $this->line;
    }


    /**
     * Returns the class where this function is located
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }


    /**
     * Returns the call that was made
     *
     * @return string
     */
    public function getCall(): string
    {
        $class = ($this->class ? $this->class . '::' : null);

        return $class . $this->function . '()';
    }


    /**
     * Returns the location where this call was made
     *
     * @return string
     */
    public function getLocation(): string
    {
        return $this->file . '@' . $this->line;
    }


    /**
     * Returns the arguments given to this function call
     *
     * @return IteratorInterface
     */
    public function getArguments(): IteratorInterface
    {
        return $this->arguments;
    }
}

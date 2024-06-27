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
     * @var string $function
     */
    protected string $function;

    /**
     *
     *
     * @var string $file
     */
    protected string $file;

    /**
     *
     *
     * @var int $line
     */
    protected int $line;

    /**
     * @var IteratorInterface $arguments
     */
    protected IteratorInterface $arguments;


    /**
     * FunctionCall class constructor
     *
     * @param int $offset
     */
    public function __construct(int $offset = 0)
    {
        $offset++;

        $this->file      = Debug::currentFile($offset);
        $this->line      = Debug::currentLine($offset);
        $this->function  = Debug::currentFunction($offset);
        $this->class     = Debug::currentClass($offset);
        $this->arguments = new Iterator(Debug::currentArguments($offset));
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
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }


    /**
     * Returns the line where this function is located
     *
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }


    /**
     * Returns the class where this function is located
     *
     * @return int
     */
    public function getClass(): int
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

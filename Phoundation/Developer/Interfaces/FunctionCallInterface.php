<?php

namespace Phoundation\Developer\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;

interface FunctionCallInterface
{
    /**
     * Returns the function (or method) of this call
     *
     * @return string
     */
    public function getFunction(): string;

    /**
     * Returns the file where this function is located
     *
     * @return string
     */
    public function getFile(): string;

    /**
     * Returns the line where this function is located
     *
     * @return int
     */
    public function getLine(): int;

    /**
     * Returns the class where this function is located
     *
     * @return int
     */
    public function getClass(): int;

    /**
     * Returns the arguments given to this function call
     *
     * @return IteratorInterface
     */
    public function getArguments(): IteratorInterface;

    /**
     * Returns the call that was made
     *
     * @return string
     */
    public function getCall(): string;

    /**
     * Returns the location where this call was made
     *
     * @return string
     */
    public function getLocation(): string;
}

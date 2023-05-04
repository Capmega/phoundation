<?php

declare(strict_types=1);

namespace Phoundation\Developer;

class Statistic
{
    /**
     * @var float|null
     */
    protected ?float $time = null;

    /**
     * @var string|null
     */
    protected ?string $title = null;

    /**
     * @var string|null
     */
    protected ?string $query = null;

    /**
     * @var string|null
     */
    protected ?string $class = null;

    /**
     * @var string|null
     */
    protected ?string $function = null;

    /**
     * @var string|null
     */
    protected ?string $file = null;

    /**
     * @var string|null
     */
    protected ?string $line = null;


    /**
     * Statistics constructor
     */
    public function __construct()
    {
        $this->class = Debug::currentClass(-1);
        $this->function = Debug::currentFunction(-1);
        $this->file = Debug::currentFile(-1);
        $this->line = Debug::currentLine(-1);
    }


    /**
     * Set the time for this statistic
     *
     * @param float $time
     * @return Statistic
     */
    public function setTime(float $time): Statistic
    {
        $this->time = $time;
        return $this;
    }


    /**
     * Returns the time for this statistic
     *
     * @return float $time
     */
    public function getTime(): float
    {
        return $this->time;
    }


    /**
     * Set the query for this statistic
     *
     * @param string $query
     * @return Statistic
     */
    public function setQuery(string $query): Statistic
    {
        $this->query = $query;
        return $this;
    }


    /**
     * Returns the query for this statistic
     *
     * @return string $query
     */
    public function getQuery(): string
    {
        return $this->query;
    }


    /**
     * Returns the class for this statistic
     *
     * @return string $class
     */
    public function getClass(): string
    {
        return $this->class;
    }


    /**
     * Returns the function for this statistic
     *
     * @return string $function
     */
    public function getFunction(): string
    {
        return $this->function;
    }


    /**
     * Returns the file for this statistic
     *
     * @return string $file
     */
    public function getFile(): string
    {
        return $this->file;
    }


    /**
     * Returns the line for this statistic
     *
     * @return string $line
     */
    public function getLine(): string
    {
        return $this->line;
    }
}
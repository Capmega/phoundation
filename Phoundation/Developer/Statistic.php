<?php

declare(strict_types=1);

namespace Phoundation\Developer;

class Statistic
{
    /**
     * The time when this statistic was created
     *
     * @var float|null $time
     */
    protected ?float $time = null;

    /**
     * The user readable title label for this statistic
     *
     * @var string|null $title
     */
    protected ?string $title = null;

    /**
     * The query line for this statistic
     *
     * @var string|null $query
     */
    protected ?string $query = null;

    /**
     * The class information for this statistic
     *
     * @var string|null $class
     */
    protected ?string $class = null;

    /**
     * The function or method call for this statistic
     *
     * @var string|null $function
     */
    protected ?string $function = null;

    /**
     * The file for this statistic
     *
     * @var string|null $file
     */
    protected ?string $file = null;

    /**
     * The line in the file for this statistic
     *
     * @var int|null $line
     */
    protected ?int $line = null;


    /**
     * Statistics constructor
     */
    public function __construct()
    {
        $this->file     = Debug::currentFile(-1);
        $this->line     = Debug::currentLine(-1);
        $this->class    = Debug::currentClass(-1);
        $this->function = Debug::currentFunction(-1);
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
     * @return int $line
     */
    public function getLine(): int
    {
        return $this->line;
    }
}
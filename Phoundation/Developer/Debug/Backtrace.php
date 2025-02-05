<?php

/**
 * Class Backtrace
 *
 * This object contains and manages backtrace data. It can generate backtrace data directly or rendered for CLI or web
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Debug;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Entry;


class Backtrace extends Entry
{
    /**
     * Tracks if args are in the backtrace data
     *
     * @var bool $args
     */
    protected bool $args = false;

    /**
     * Tracks if the backtrace should start at the real beginning of the array or at the moment the program started
     *
     * @var bool $start_begin
     */
    protected bool $start_begin = true;

    /**
     * Tracks if the backtrace should stop at the real end of the array or at the moment the program started
     *
     * @var bool $stop_end
     */
    protected bool $stop_end = false;


    /**
     * Backtrace class constructor
     *
     * @param ArrayableInterface|array|null $source
     */
    public function __construct(ArrayableInterface|array|null $source = null) {
        parent::__construct($source);
        $this->source = debug_backtrace();
    }


    /**
     * Returns a static object
     *
     * @param ArrayableInterface|array|null $source
     *
     * @return static
     */
    public static function new(ArrayableInterface|array|null $source = null): static
    {
        return new static($source);
    }


    /**
     * Returns a static object starting at the moment pho or index.php started up and ending when a program started
     *
     * @param ArrayableInterface|array|null $source
     *
     * @return static
     */
    public static function newStartup(ArrayableInterface|array|null $source = null): static
    {
        return static::new($source)->setStopEnd(false);
    }


    /**
     * Returns a static object starting when a program started
     *
     * @param ArrayableInterface|array|null $source
     *
     * @return static
     */
    public static function newProgram(ArrayableInterface|array|null $source = null): static
    {
        return static::new($source)->setStartBegin(false);
    }


    /**
     * Returns if args are in the backtrace data
     *
     * @return bool
     */
    public function getArgs(): bool
    {
        return $this->args;
    }


    /**
     * Sets if args are in the backtrace data
     *
     * @param bool $args
     *
     * @return static
     */
    public function setArgs(bool $args): static
    {
        $this->args = $args;
        return $this;
    }


    /**
     * Returns if the backtrace should start at the real beginning of the array or at the moment the program started
     *
     * @return bool
     */
    public function getStartBegin(): bool
    {
        return $this->start_begin;
    }


    /**
     * Sets if the backtrace should start at the real beginning of the array or at the moment the program started
     *
     * @param bool $start_begin
     *
     * @return static
     */
    public function setStartBegin(bool $start_begin): static
    {
        $this->start_begin = $start_begin;
        return $this;
    }


    /**
     * Returns if the backtrace should stop at the real end of the array or at the moment the program started
     *
     * @return bool
     */
    public function getStopEnd(): bool
    {
        return $this->stop_end;
    }


    /**
     * Sets if the backtrace should stop at the real end of the array or at the moment the program started
     *
     * @param bool $stop_end
     *
     * @return static
     */
    public function setStopEnd(bool $stop_end): static
    {
        $this->stop_end = $stop_end;
        return $this;
    }
}

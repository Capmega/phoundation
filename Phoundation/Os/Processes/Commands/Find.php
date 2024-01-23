<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Files;
use Phoundation\Filesystem\Interfaces\FilesInterface;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Stringable;


/**
 * Class Find
 *
 * This class manages the "find" command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Find extends Command implements FindInterface
{
    /**
     * The path in which to find
     *
     * @var string $path
     */
    protected string $path;

    /**
     * The type of file to filter on
     *
     * @var string|null $type
     */
    protected ?string $type = null;

    /**
     * The file size to filter on
     *
     * @var string|null $size
     */
    protected ?string $size = null;

    /**
     * Tracks if each directory's contents before the directory itself.  The -delete action also implies
     *
     * @var bool|null $follow_symlinks
     */
    protected ?bool $follow_symlinks = false;

    /**
     * Process each directory’s contents before the directory itself.  The -delete action also implies -depth.
     *
     * @var bool $depth
     */
    protected bool $depth = false;

    /**
     * Do not apply any tests or actions at levels less than levels (a non‐negative integer).  Using -mindepth 1 means
     * process all files except the starting‐points.
     *
     * @var int|null $min_depth
     */
    protected ?int $min_depth = null;

    /**
     * Descend  at  most levels (a non‐negative integer) levels of directories below the starting‐points.  Using
     * maxdepth 0 means only apply the tests and actions to the start‐ing‐points themselves.
     *
     * @var int|null $max_depth
     */
    protected ?int $max_depth = null;

    /**
     * The callback to execute on each file
     *
     * @var callable|null $callback
     */
    protected ?callable $callback = null;

    /**
     * The action to execute
     *
     * @var string|null $action
     */
    protected ?string $action = null;

    /**
     * The action to execute
     *
     * @var string|null $action_command
     */
    protected ?string $action_command = null;


    /**
     * Returns the path in which to find
     *
     * @return string
     */
    public function getPath(): string
    {
        if (!isset($this->path)) {
            throw new OutOfBoundsException(tr('Cannot return path, no path has been specified yet'));
        }

        return $this->path;
    }


    /**
     * Sets the path in which to find
     *
     * @param Stringable|string $path
     * @return $this
     */
    public function setPath(Stringable|string $path): static
    {
        $this->path = (string) $path;
        return $this->setExecutionDirectory($path);
    }


    /**
     * Returns the size in which to find
     *
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }


    /**
     * Sets the size in which to find
     *
     * @param Stringable|string $size
     * @return $this
     */
    public function setSize(Stringable|string $size): static
    {
        $size = (string) $size;

        if (!preg_match('/^[-+]?[0-9_]+$/', $size)) {
            throw new OutOfBoundsException(tr('Invalid size ":size" specified, must be either NUMBER (exact), -NUMBER (smaller than), or +NUMBER (larger than)', [
                ':size' => $size
            ]));
        }

        $this->size = str_replace('_', '', $size);
        return $this;
    }


    /**
     * Returns the depth in which to find
     *
     * @return bool
     */
    public function getDepth(): bool
    {
        return $this->depth;
    }


    /**
     * Sets the depth in which to find
     *
     * @param bool $depth
     * @return $this
     */
    public function setDepth(bool $depth): static
    {
        $this->depth = $depth;
        return $this;
    }


    /**
     * Returns the min_depth in which to find
     *
     * @return int|null
     */
    public function getMinDepth(): ?int
    {
        return $this->min_depth;
    }


    /**
     * Sets the min_depth in which to find
     *
     * @param int|null $min_depth
     * @return $this
     */
    public function setMinDepth(?int $min_depth): static
    {
        $this->min_depth = $min_depth;
        return $this;
    }


    /**
     * Returns the max_depth in which to find
     *
     * @return int|null
     */
    public function getMaxDepth(): ?int
    {
        return $this->max_depth;
    }


    /**
     * Sets the max_depth in which to find
     *
     * @param int|null $max_depth
     * @return $this
     */
    public function setMaxDepth(?int $max_depth): static
    {
        $this->max_depth = $max_depth;
        return $this;
    }


    /**
     * Returns the callback in which to find
     *
     * @return callable|null
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }


    /**
     * Sets the callback in which to find
     *
     * @param callable|null $callback
     * @return $this
     */
    public function setCallback(?callable $callback): static
    {
        if ($this->action) {
            throw new OutOfBoundsException(tr('Cannot specify callback for find, action has already been defined'));
        }

        $this->callback = $callback;
        return $this;
    }


    /**
     * Returns the action in which to find
     *
     * @return callable|null
     */
    public function getAction(): ?callable
    {
        return $this->action;
    }


    /**
     * Sets the action in which to find
     *
     * @param callable|null $action
     * @param string|null $action_command
     * @return $this
     */
    public function setAction(?callable $action, ?string $action_command = null): static
    {
        if ($this->callback) {
            throw new OutOfBoundsException(tr('Cannot specify action for find, callback has already been defined'));
        }

        $actions = ['b', 'c', 'd', 'p', 'f', 'l', 's'];

        if (!in_array($action, $actions)) {
            throw new OutOfBoundsException(tr('Invalid action ":action" specified, must be one of "delete, exec"', [
                ':action' => $action
            ]));
        }

        $this->action         = $action;
        $this->action_command = $action_command

        return $this;
    }


    /**
     * Returns the type in which to find
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }


    /**
     * Sets the type in which to find
     *
     * Allowed types:
     *
     * b      block (buffered) special
     * c      character (unbuffered) special
     * d      directory
     * p      named pipe (FIFO)
     * f      regular file
     * l      symbolic  link;  this  is  never  true if the -L option or the -follow option is in effect, unless the
     *        symbolic link is broken.  If you want to search for symboliclinks when -L is in effect, use -xtype.
     * s      socket
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): static
    {
        $types = ['b', 'c', 'd', 'p', 'f', 'l', 's'];

        if (!in_array($type, $types)) {
            throw new OutOfBoundsException(tr('Invalid type ":type" specified, must be one of "b, c, d, p, f, l, s"', [
                ':type' => $type
            ]));
        }

        $this->type = $type;
        return $this;
    }


    /**
     * Returns a Files-object containing the found files
     *
     * @return FilesInterface|null
     */
    public function find(): ?FilesInterface
    {
        if (!isset($this->path)) {
            throw new OutOfBoundsException(tr('Cannot execute find, no path has been specified'));
        }

        $this->setCommand('find')
             ->setTimeout($this->timeout)
             ->addArgument($this->path)
             ->addArguments($this->type      ? ['-type'    , $this->type]      : null)
             ->addArguments($this->size      ? ['-size'    , $this->size]      : null)
             ->addArguments($this->depth     ? ['-depth'   , $this->depth]     : null)
             ->addArguments($this->max_depth ? ['-maxdepth', $this->max_depth] : null)
             ->addArguments($this->min_depth ? ['-mindepth', $this->min_depth] : null)
             ->addArguments($this->size      ? ['-size'    , $this->size]      : null);

        if ($this->action) {
            $this->addArguments(['-' . $this->action, $this->action_command])
                 ->executeNoReturn();

            return null;
        }

        $output = $this->executeReturnArray();

        if ($this->callback) {
            $this->callback($output);
        }

        return Files::new()->setSource($output);
    }
}

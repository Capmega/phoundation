<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Data\Traits\DataName;
use Phoundation\Data\Traits\DataPath;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Files;
use Phoundation\Filesystem\Interfaces\FilesInterface;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\Path;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
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
    use DataName;
    use DataPath {
        setPath as protected __setPath;
    }


    /**
     * Tracks to follow symlinks or not
     *
     * @var bool $follow_symlinks
     */
    protected bool $follow_symlinks = false;

    /**
     * The callback to execute on each found file
     *
     * @var mixed $callback
     */
    protected mixed $callback = null;

    /**
     * The shell command to execute on each file
     *
     * @var string|null $exec
     */
    protected ?string $exec = null;

    /**
     * Find empty files
     *
     * @var bool $empty
     */
    protected bool $empty = false;

    /**
     * Tracks if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     * @var bool $mount
     */
    protected bool $mount = true;

    /**
     * The file last modified time in minutes
     *
     * @var string|null $mtime
     */
    protected ?string $mtime = null;

    /**
     * The file last access time in minutes
     *
     * @var string|null $atime
     */
    protected ?string $atime = null;

    /**
     * The file last status change time in minutes
     *
     * @var string|null $ctime
     */
    protected ?string $ctime = null;

    /**
     * The iname to filter on
     *
     * @var string|null $iname
     */
    protected ?string $iname = null;

    /**
     * The type of file to filter on
     *
     * @var string|null $type
     */
    #[ExpectedValues(['b', 'c', 'd', 'p', 'f', 'l', 's'])]
    protected ?string $type = null;

    /**
     * The regex  to filter on
     *
     * @var string|null $regex
     */
    protected ?string $regex = null;

    /**
     * The file size to filter on
     *
     * @var string|null $size
     */
    protected ?string $size = null;

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
     * Find the specified path
     *
     * @var string|null $find_path
     */
    protected ?string $find_path = null;


    /**
     * Sets the path in which to find
     *
     * @param PathInterface|string|null $path
     * @return $this
     */
    public function setPath(PathInterface|string|null $path): static
    {
        $this->__setPath($path);
        return $this->setExecutionDirectory($this->path);
    }


    /**
     * Sets if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     * @param PathInterface|string|null $find_path
     * @return static
     */
    public function setFindPath(PathInterface|string|null $find_path): static
    {
        $this->find_path = $find_path;
        return $this;
    }


    /**
     * Returns if find should find empty files
     *
     * @return string|null
     */
    public function getFindPath(): ?string
    {
        return $this->find_path;
    }


    /**
     * Sets if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     * @param bool $mount
     * @return static
     */
    public function setMount(bool $mount): static
    {
        $this->mount = $mount;
        return $this;
    }


    /**
     * Returns if find should find empty files
     *
     * @return bool
     */
    public function getMount(): bool
    {
        return $this->mount;
    }


    /**
     * Returns if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     * @return bool
     */
    public function getFollowSymlinks(): bool
    {
        return $this->follow_symlinks;
    }


    /**
     * Sets if find should find follow_symlinks files
     *
     * @param bool $follow_symlinks
     * @return static
     */
    public function setFollowSymlinks(bool $follow_symlinks): static
    {
        $this->follow_symlinks = $follow_symlinks;
        return $this;
    }


    /**
     * Returns if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     * @return bool
     */
    public function getEmpty(): bool
    {
        return $this->empty;
    }


    /**
     * Sets if find should find empty files
     *
     * @param bool $empty
     * @return static
     */
    public function setEmpty(bool $empty): static
    {
        $this->empty = $empty;
        return $this;
    }


    /**
     * Returns the iname
     *
     * @return string|null
     */
    public function getIname(): ?string
    {
        return $this->iname;
    }


    /**
     * Sets the iname
     *
     * @param string|null $iname
     * @return static
     */
    public function setIname(?string $iname): static
    {
        $this->iname = $iname;
        return $this;
    }


    /**
     * Returns the size for which to look
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
     * Returns the last modified time in minutes for which to look
     *
     * @return string
     */
    public function getMtime(): string
    {
        return $this->mtime;
    }


    /**
     * Sets the last modified time in minutes for which to find
     *
     * @param Stringable|string $mtime
     * @return $this
     */
    public function setMtime(Stringable|string $mtime): static
    {
        $mtime = (string) $mtime;

        if (!preg_match('/^[-+]?[0-9_]+$/', $mtime)) {
            throw new OutOfBoundsException(tr('Invalid mtime ":mtime" specified, must be either NUMBER (exact), -NUMBER (smaller than), or +NUMBER (larger than)', [
                ':mtime' => $mtime
            ]));
        }

        $this->mtime = str_replace('_', '', $mtime);
        return $this;
    }


    /**
     * Returns the access time in minutes for which to look
     *
     * @return string
     */
    public function getAtime(): string
    {
        return $this->atime;
    }


    /**
     * Sets the access time in minutes for which to find
     *
     * @param Stringable|string $atime
     * @return $this
     */
    public function setAtime(Stringable|string $atime): static
    {
        $atime = (string) $atime;

        if (!preg_match('/^[-+]?[0-9_]+$/', $atime)) {
            throw new OutOfBoundsException(tr('Invalid atime ":atime" specified, must be either NUMBER (exact), -NUMBER (smaller than), or +NUMBER (larger than)', [
                ':atime' => $atime
            ]));
        }

        $this->atime = str_replace('_', '', $atime);
        return $this;
    }


    /**
     * Returns the file status change time in minutes for which to look
     *
     * @return string
     */
    public function getCtime(): string
    {
        return $this->ctime;
    }


    /**
     * Sets the file status change time in minutes for which to find
     *
     * @param Stringable|string $ctime
     * @return $this
     */
    public function setCtime(Stringable|string $ctime): static
    {
        $ctime = (string) $ctime;

        if (!preg_match('/^[-+]?[0-9_]+$/', $ctime)) {
            throw new OutOfBoundsException(tr('Invalid ctime ":ctime" specified, must be either NUMBER (exact), -NUMBER (smaller than), or +NUMBER (larger than)', [
                ':ctime' => $ctime
            ]));
        }

        $this->ctime = str_replace('_', '', $ctime);
        return $this;
    }


    /**
     * Returns the file types for which to look
     *
     * @return string
     */
    public function getTypes(): string
    {
        return $this->types;
    }


    /**
     * Sets the file types in which to find
     *
     * @param Stringable|array|string $types
     * @return $this
     */
    public function setTypes(Stringable|array|string $types): static
    {
        $types = Arrays::force($types);

        foreach ($types as &$type) {
            $type = match($type) {
                'directory'        => 'd',
                'fifo device'      => 'p',
                'character device' => 'c',
                'block device'     => 'b',
                'regular file'     => 'f',
                'socket file'      => 's',
                'symbolic link'    => 'l',
                'd'                => 'd',
                'p'                => 'p',
                'c'                => 'c',
                'b'                => 'b',
                'f'                => 'f',
                's'                => 's',
                'l'                => 'l',
            };
        }

        unset($type);

        $this->types = Strings::force($types, ',');
        return $this;
    }


    /**
     * Returns the regex in which to find
     *
     * @return string|null
     */
    public function getRegex(): ?string
    {
        return $this->regex;
    }


    /**
     * Sets the regex in which to find
     *
     * @param string|null $regex
     * @return $this
     */
    public function setRegex(?string $regex): static
    {
        $this->regex = $regex;
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
        if ($this->exec) {
            throw new OutOfBoundsException(tr('Cannot specify callback for find, exec has already been defined'));
        }

        $this->callback = $callback;
        return $this;
    }


    /**
     * Returns what shell command to execute on each file
     *
     * @return string|null
     */
    public function getExec(): ?string
    {
        return $this->exec;
    }


    /**
     * Sets what shell command to execute on each file
     *
     * @param string|null $exec
     * @return $this
     */
    public function setExec(?string $exec = null): static
    {
        if ($this->callback) {
            throw new OutOfBoundsException(tr('Cannot specify exec for find, a callback has already been defined'));
        }

        $this->exec = $exec;
        return $this;
    }


    /**
     * Returns the type in which to find
     *
     * @return string
     */
    #[ExpectedValues(['b', 'c', 'd', 'p', 'f', 'l', 's'])]
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
    public function setType(#[ExpectedValues(['b', 'c', 'd', 'p', 'f', 'l', 's'])] string $type): static
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
     * @return FilesInterface
     */
    public function getFoundFiles(): FilesInterface
    {
        $files = Files::new()->setSource($this->output);

        if ($this->callback) {
            $callback = $this->callback;
            $callback($files);
        }

        return $files;
    }


    /**
     * Returns an array containing the found files
     *
     * @return array
     */
    public function executeReturnArray(): array
    {
        if (!$this->path) {
            throw new OutOfBoundsException(tr('Cannot execute find, no path has been specified'));
        }

        try {
            $this->setCommand('find')
                ->setTimeout($this->timeout)
                ->addArgument($this->path)
                ->addArguments($this->mount           ? '-mount'                        : null)
                ->addArguments($this->empty           ? '-empty'                        : null)
                ->addArguments($this->follow_symlinks ? '-L'                            : null)
                ->addArguments($this->name            ? ['-name'    , $this->name]      : null)
                ->addArguments($this->iname           ? ['-iname'   , $this->iname]     : null)
                ->addArguments($this->find_path       ? ['-path'    , $this->find_path] : null)
                ->addArguments($this->atime           ? ['-amin'    , $this->atime]     : null)
                ->addArguments($this->ctime           ? ['-cmin'    , $this->ctime]     : null)
                ->addArguments($this->mtime           ? ['-mmin'    , $this->mtime]     : null)
                ->addArguments($this->type            ? ['-type'    , $this->type]      : null)
                ->addArguments($this->regex           ? ['-regex'   , $this->regex]     : null)
                ->addArguments($this->size            ? ['-size'    , $this->size]      : null)
                ->addArguments($this->depth           ? ['-depth'   , $this->depth]     : null)
                ->addArguments($this->max_depth       ? ['-maxdepth', $this->max_depth] : null)
                ->addArguments($this->min_depth       ? ['-mindepth', $this->min_depth] : null)
                ->addArguments($this->size            ? ['-size'    , $this->size]      : null)
                ->addArguments($this->exec            ? ['-exec'    , $this->exec]      : null);

        } catch (ProcessFailedException $e) {
            Path::new($this->path)->checkReadable('find', $e);
        }

        return parent::executeReturnArray();
    }


    /**
     * Returns a Files-object containing the found files
     *
     * @return FilesInterface
     */
    public function executeReturnFiles(): FilesInterface
    {
        return Files::new($this->executeReturnArray(), $this->restrictions);
    }
}

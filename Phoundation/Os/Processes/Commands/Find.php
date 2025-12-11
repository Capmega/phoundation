<?php

/**
 * Class Find
 *
 * This class manages the "find" command
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 *
 * @todo Add support for multiple search paths
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Data\Traits\TraitDataResultsWithPermissionDenied;
use Phoundation\Data\Traits\TraitDataStringName;
use Phoundation\Data\Traits\TraitDataObjectPath;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFiles;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Os\Processes\Commands\Exception\FindException;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use Stringable;


class Find extends Command implements FindInterface
{
    use TraitDataResultsWithPermissionDenied;
    use TraitDataStringName;
    use TraitDataObjectPath {
        setPathObject as protected __setPathObject;
    }


    /**
     * Result files cache
     *
     * @var PhoFilesInterface|null
     */
    protected ?PhoFilesInterface $files;

    /**
     * Tracks to follow symlinks or not
     *
     * @var bool $follow_symlinks
     */
    protected bool $follow_symlinks = false;

    /**
     * The fck to execute on each found file
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
     * Tracks if the found files should be deleted
     *
     * @var bool $delete
     */
    protected bool $delete = false;

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
    #[ExpectedValues([
        'b',
        'c',
        'd',
        'p',
        'f',
        'l',
        's',
    ])]
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
     * Do not apply any Tests or actions at levels less than levels (a non‐negative integer).  Using -mindepth 1 means
     * process all files except the starting‐points.
     *
     * @var int|null $min_depth
     */
    protected ?int $min_depth = null;

    /**
     * Descend  at  most levels (a non‐negative integer) levels of directories below the starting‐points.  Using
     * maxdepth 0 means only apply the Tests and actions to the start‐ing‐points themselves.
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
     * Find the specified path
     *
     * @var PhoDirectoryInterface|null $find_path
     */
    protected ?PhoDirectoryInterface $find_path = null;

    /**
     * Tracks if "permission denied" in the results should be ignored or not
     *
     * @var bool $ignore_permission_denied_in_results
     */
    protected bool $ignore_permission_denied_in_results = false;


    /**
     * Find class constructor
     *
     * @param PhoDirectoryInterface|PhoRestrictionsInterface|null $execution_directory
     * @param \Stringable|string|null                             $operating_system
     * @param string|null                                         $packages
     */
    public function __construct(PhoDirectoryInterface|PhoRestrictionsInterface|null $execution_directory = null, Stringable|string|null $operating_system = null, ?string $packages = null) {
        parent::__construct($execution_directory, $operating_system, $packages);

        if ($execution_directory instanceof PhoDirectoryInterface) {
            $this->setPathObject($execution_directory);
        }
    }


    /**
     * Sets the path in which to find
     *
     * @param PhoPathInterface|null $o_path
     *
     * @return static
     */
    public function setPathObject(?PhoPathInterface $o_path): static
    {
        return $this->__setPathObject($o_path);
    }


    /**
     * Returns if find should find empty files
     *
     * @return PhoDirectoryInterface|null
     */
    public function getFindPath(): ?PhoDirectoryInterface
    {
        return $this->find_path;
    }


    /**
     * Sets if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     *
     * @param PhoPathInterface|null $find_path
     *
     * @return static
     */
    public function setFindPath(PhoPathInterface|null $find_path): static
    {
        $this->find_path = $find_path;

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
     * Sets if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     *
     * @param bool $mount
     *
     * @return static
     */
    public function setMount(bool $mount): static
    {
        $this->mount = $mount;

        return $this;
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
     *
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
     *
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
     *
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
     *
     * @return static
     */
    public function setSize(Stringable|string $size): static
    {
        $size = (string) $size;
        if (!preg_match('/^[-+]?[0-9_]+$/', $size)) {
            throw new OutOfBoundsException(tr('Invalid size ":size" specified, must be either NUMBER (exact), -NUMBER (smaller than), or +NUMBER (larger than)', [
                ':size' => $size,
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
     *
     * @return static
     */
    public function setMtime(Stringable|string $mtime): static
    {
        $mtime = (string) $mtime;
        if (!preg_match('/^[-+]?[0-9_]+$/', $mtime)) {
            throw new OutOfBoundsException(tr('Invalid mtime ":mtime" specified, must be either NUMBER (exact), -NUMBER (smaller than), or +NUMBER (larger than)', [
                ':mtime' => $mtime,
            ]));
        }
        $this->mtime = str_replace('_', '', $mtime);

        return $this;
    }


    /**
     * Returns if permission denied in result set should be ignored or not
     *
     * @return bool
     */
    public function getIgnorePermissionDeniedInResults(): bool
    {
        return $this->ignore_permission_denied_in_results;
    }


    /**
     * Sets if permission denied in result set should be ignored or not
     *
     * @param bool $ignore_permission_denied_in_results
     *
     * @return static
     */
    public function setIgnorePermissionDeniedInResults(bool $ignore_permission_denied_in_results): static
    {
        $this->ignore_permission_denied_in_results = $ignore_permission_denied_in_results;
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
     *
     * @return static
     */
    public function setAtime(Stringable|string $atime): static
    {
        $atime = (string) $atime;

        if (!preg_match('/^[-+]?[0-9_]+$/', $atime)) {
            throw new OutOfBoundsException(tr('Invalid atime ":atime" specified, must be either NUMBER (exact), -NUMBER (smaller than), or +NUMBER (larger than)', [
                ':atime' => $atime,
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
     *
     * @return static
     */
    public function setCtime(Stringable|string $ctime): static
    {
        $ctime = (string) $ctime;
        if (!preg_match('/^[-+]?[0-9_]+$/', $ctime)) {
            throw new OutOfBoundsException(tr('Invalid ctime ":ctime" specified, must be either NUMBER (exact), -NUMBER (smaller than), or +NUMBER (larger than)', [
                ':ctime' => $ctime,
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
     *
     * @return static
     */
    public function setTypes(Stringable|array|string $types): static
    {
        $types = Arrays::force($types);
        foreach ($types as &$type) {
            $type = match ($type) {
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function setExec(?string $exec = null): static
    {
        if ($this->callback) {
            throw new OutOfBoundsException(tr('Cannot specify exec for find, a callback has already been defined'));
        }

        if (str_ends_with($exec, '{}')) {
            $exec = Strings::untilReverse($exec, '{}');
            $exec = trim($exec);
        }

        $this->exec = $exec;
        return $this;
    }


    /**
     * Returns if all found files will be deleted
     *
     * @return bool
     */
    public function getDelete(): bool
    {
        return $this->delete;
    }


    /**
     * Sets what shell command to execute on each file
     *
     * @param bool $delete
     * @param bool $recursive
     * @return static
     */
    public function setDelete(bool $delete, bool $recursive = false): static
    {
        $this->delete = $delete;
        return $this;
    }


    /**
     * Returns the type in which to find
     *
     * @return string
     */
    #[ExpectedValues([
        'b',
        'c',
        'd',
        'p',
        'f',
        'l',
        's',
    ])]
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
     *
     * @return static
     */
    public function setType(#[ExpectedValues([
        'b',
        'c',
        'd',
        'p',
        'f',
        'l',
        's',
    ])] string $type): static
    {
        $types = [
            'b',
            'c',
            'd',
            'p',
            'f',
            'l',
            's',
        ];
        if (!in_array($type, $types)) {
            throw new OutOfBoundsException(tr('Invalid type ":type" specified, must be one of "b, c, d, p, f, l, s"', [
                ':type' => $type,
            ]));
        }
        $this->type = $type;

        return $this;
    }


    /**
     * Returns a PhoFiles-object containing the found files
     *
     * @return PhoFilesInterface
     */
    public function getFoundFiles(): PhoFilesInterface
    {
        return PhoFiles::new($this->o_path, $this->output, $this->o_restrictions);
    }


    /**
     * Returns a PhoFiles-object containing the found files
     *
     * @return PhoFilesInterface
     */
    public function getFiles(): PhoFilesInterface
    {
        if (empty($this->files)) {
            $this->files = PhoFiles::new($this->o_path, $this->executeReturnArray(), $this->o_restrictions);
        }

        return $this->files;
    }


    /**
     * Returns an array containing the found files
     *
     * @return array
     */
    public function executeReturnArray(): array
    {
        if (!$this->o_path) {
            throw new OutOfBoundsException(tr('Cannot execute find, no path has been specified'));
        }

        try {
            $this->setCommand('find')
                 ->setTimeout($this->timeout)
                 ->addArgument($this->o_path->getSource())
                 ->addArguments($this->mount           ? '-mount'                                     : null)
                 ->addArguments($this->empty           ? '-empty'                                     : null)
                 ->addArguments($this->delete          ? ['-delete']                                  : null)
                 ->addArguments($this->follow_symlinks ? '-L'                                         : null)
                 ->addArguments($this->name            ? ['-name'    , $this->name]                   : null)
                 ->addArguments($this->iname           ? ['-iname'   , $this->iname]                  : null)
                 ->addArguments($this->find_path       ? ['-path'    , $this->find_path->getSource()] : null)
                 ->addArguments($this->atime           ? ['-amin'    , $this->atime]                  : null)
                 ->addArguments($this->ctime           ? ['-cmin'    , $this->ctime]                  : null)
                 ->addArguments($this->mtime           ? ['-mmin'    , $this->mtime]                  : null)
                 ->addArguments($this->type            ? ['-type'    , $this->type]                   : null)
                 ->addArguments($this->regex           ? ['-regex'   , $this->regex]                  : null)
                 ->addArguments($this->size            ? ['-size'    , $this->size]                   : null)
                 ->addArguments($this->depth           ? ['-depth'   , $this->depth]                  : null)
                 ->addArguments($this->max_depth       ? ['-maxdepth', $this->max_depth]              : null)
                 ->addArguments($this->min_depth       ? ['-mindepth', $this->min_depth]              : null)
                 ->addArguments($this->size            ? ['-size'    , $this->size]                   : null)
                 ->addArguments($this->exec            ? ['-exec'    , $this->exec, '{}', ';']        : null);

        } catch (ProcessFailedException $e) {
            PhoPath::new($this->o_path)
                   ->checkReadable('find', $e);
        }

        try {
            // Clear files cache and execute the find command
            unset($this->files);
            parent::executeReturnArray();

        } catch (ProcessFailedException $e) {
            $output  = $e->getDataKey('output');
            $matches = Arrays::keepMatchingValues($output, 'Permission denied', flags: Utils::MATCH_CASE_INSENSITIVE | Utils::MATCH_ENDS_WITH);

            if (count($matches) and $this->ignore_permission_denied_in_results) {
                // Some results had permission denied, we can safely ignore these as long as we flag it

                // Clean failed matches entries, should only have the filename in there
                $matches = Arrays::replaceValuesWithCallbackReturn($matches, function ($key, $value) {
                    $value = Strings::cut($value, '‘', '’');
                    return trim($value);
                });

                // Set exit code back to 0 and fill output with all the entries that do NOT have permiossion denied
                $this->exit_code = 0;
                $this->output    = Arrays::removeMatchingValues($output, 'Permission denied', flags: Utils::MATCH_CASE_INSENSITIVE | Utils::MATCH_ENDS_WITH);

                $this->setResultsWithPermissionDenied(Arrays::valueToKeys($matches));
            }
        }

        // If -exec was specified, the command has to exist and has to be properly escaped, or we will end up with all
        // "No such file or directory" errors which will NOT set the exit code!
        if ($this->getExec()) {
            $first = array_first($this->output);
            $first = strtolower($first);

            if (str_contains($first, 'no such file or directory')) {
                throw FindException::new(ts('Invalid or non existing exec command ":exec" specified', [
                    ':exec' => $this->exec
                ]))->addMessages(ts(ts('This means that the specified exec program "exec" for the results either does not exist (requires a package to be installed, perhaps?), or the command was not properly escaped, causing find to think that (for example) "ls -l" is a single complete command')));
            }
        }

        // The output array should have keys the same as values
        $this->output = Arrays::valueToKeys($this->output);

        // Execute callbacks?
        if ($this->callback) {
            $callback = $this->callback;

            foreach ($this->output as $file) {
                $callback($file);
            }
        }

        return $this->output;
    }
}

<?php

/**
 * FsRestrictions class
 *
 * This class manages file access restrictions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Cli\CliCommand;
use Phoundation\Core\Core;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Web;
use Stringable;

class FsRestrictions implements FsRestrictionsInterface
{
    /**
     * Internal store of all restrictions
     *
     * @var array $source
     */
    protected array $source = [];

    /**
     * FsRestrictions name
     *
     * @var string $label
     */
    protected string $label;


    /**
     * FsRestrictions constructor
     *
     * @param Stringable|string|array|null $directories
     * @param bool                         $write
     * @param string|null                  $label
     */
    public function __construct(Stringable|string|array|null $directories = null, bool $write = false, ?string $label = null)
    {
        if ($label) {
            $this->label = $label;

        } else {
            // Autodetect the label, it should be the function call name (or class::method()) that called this
            $call        = Debug::getCallBefore(null, FsRestrictions::class);
            $this->label = $call->getCall();

            if ($this->label === 'include()') {
                // This is actually the main command or web page, so show that instead
                if (PLATFORM_CLI) {
                    $this->label = tr('Command :command', [':command' => CliCommand::getCommandsString()]);

                } else {
                    $this->label = tr('Web page :page', [':page' => Request::getExecutedPath()]);
                }
            }
        }

        if ($directories) {
            $this->setSource($directories, $write);
        }
    }


    /**
     * Returns a new writable FsRestrictions object with the specified restrictions
     *
     * @param Stringable|string|array|null $directories
     * @param string|null                  $label
     *
     * @return static
     */
    public static function getWritable(Stringable|string|array|null $directories = null, ?string $label = null): static
    {
        return new static($directories, true, $label);
    }


    /**
     * Returns a new readonly FsRestrictions object with the specified restrictions
     *
     * @param Stringable|string|array|null $directories
     * @param string|null                  $label
     *
     * @return static
     */
    public static function getReadonly(Stringable|string|array|null $directories = null, ?string $label = null): static
    {
        return new static($directories, false, $label);
    }


    /**
     * Returns a restrictions object for DIRECTORY_ROOT
     *
     * @param bool        $write
     * @param string|null $label
     *
     * @return static
     */
    public static function getRoot(bool $write = false, ?string $label = null): static
    {
        return new static(DIRECTORY_ROOT, $write, $label);
    }


    /**
     * Returns a restrictions object for DIRECTORY_DATA
     *
     * @param bool        $write
     * @param string|null $label
     *
     * @return static
     */
    public static function getData(bool $write = false, ?string $label = null): static
    {
        return new static(DIRECTORY_DATA, $write, $label);
    }


    /**
     * Returns a restrictions object for DIRECTORY_TMP
     *
     * @param bool        $write
     * @param string|null $label
     *
     * @return static
     */
    public static function getTmp(bool $write = false, ?string $label = null): static
    {
        return new static(DIRECTORY_TMP, $write, $label);
    }


    /**
     * Returns a restrictions object for DIRECTORY_PUBTMP
     *
     * @param bool        $write
     * @param string|null $label
     *
     * @return static
     */
    public static function getPubTmp(bool $write = false, ?string $label = null): static
    {
        return new static(DIRECTORY_PUBTMP, $write, $label);
    }


    /**
     * Returns a restrictions object for DIRECTORY_COMMANDS
     *
     * @param bool        $write
     * @param string|null $label
     *
     * @return static
     */
    public static function getCommands(bool $write = false, ?string $label = null): static
    {
        return new static(DIRECTORY_COMMANDS, $write, $label);
    }


    /**
     * Returns a restrictions object for DIRECTORY_WEB
     *
     * @param bool        $write
     * @param string|null $label
     *
     * @return static
     */
    public static function getWeb(bool $write = false, ?string $label = null): static
    {
        return new static(DIRECTORY_WEB, $write, $label);
    }


    /**
     * Returns the first specified restrictions object that is not empty, or system restrictions is all were empty
     *
     * @param FsRestrictionsInterface|null ...$restrictions
     *
     * @return static
     */
    public static function getRestrictionsOrDefault(FsRestrictionsInterface|null ...$restrictions): static
    {
        // Find the first restrictions object
        foreach ($restrictions as $restriction) {
            if ($restriction) {
                return $restriction;
            }
        }

        return static::getSystem();
    }


    /**
     * Returns either the specified restrictions object or the Core restrictions object
     *
     * With this, availability of restrictions is guaranteed, even if a function did not receive restrictions. If Core
     * restrictions are returned, these core restrictions are the ones that apply
     *
     * @param FsRestrictionsInterface|array|string|null $restrictions The restriction data that must be ensured to be a
     *                                                                FsRestrictions object
     * @param bool                                      $write        If $restrictions is not specified as a
     *                                                                FsRestrictions class, but as a directory string,
     *                                                                or array of directory strings, then this method
     *                                                                will convert that into a FsRestrictions object and
     *                                                                this is the $write modifier for that object
     * @param string|null                               $label        If $restrictions is not specified as a
     *                                                                FsRestrictions class, but as a directory string,
     *                                                                or array of directory strings, then this method
     *                                                                will convert that into a FsRestrictions object and
     *                                                                this is the $label modifier for that object
     *
     * @return FsRestrictions|null                                    An FsRestrictions object or NULL. If possible, the
     *                                                                specified restrictions will be returned but if no
     *                                                                $restictions were specified ($restrictions was
     *                                                                null or an empty string), NULL will be returned
     */
    public static function ensure(FsRestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): ?FsRestrictionsInterface
    {
        if ($restrictions) {
            if (!is_object($restrictions)) {
                // FsRestrictions were specified by simple directory string or array of directories. Convert to restrictions object
                $restrictions = new FsRestrictions($restrictions, $write, $label);
            }

            return $restrictions;
        }

        return null;
    }


    /**
     * Returns system general file access restrictions
     *
     * @return FsRestrictionsInterface
     */
    public static function getSystem(): FsRestrictionsInterface
    {
        static $restrictions;

        if (empty($restrictions)) {
            $restrictions = FsRestrictions::new(DIRECTORY_DATA, false, tr('System'));
        }

        return $restrictions;
    }


    /**
     * Returns a new FsRestrictions object with the specified restrictions
     *
     * @param Stringable|string|array|null $directories
     * @param bool                         $write
     * @param string|null                  $label
     *
     * @return static
     */
    public static function new(Stringable|string|array|null $directories = null, bool $write = false, ?string $label = null): static
    {
        return new static($directories, $write, $label);
    }


    /**
     * Return the directories for this FsRestrictions object in string format
     *
     * @return string
     */
    public function __toString(): string
    {
        return implode(',', array_keys($this->source));
    }


    /**
     * Return the directories for this FsRestrictions object
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->source;
    }


    /**
     * Returns a restrictions object with parent directories for all directories in this restrictions object
     *
     * This is useful for the Directory object where one will want to be able to access or create the parent directory
     * of the file that needs to be accessed
     *
     * @param int|null $levels
     *
     * @return FsRestrictions
     */
    public function getParent(?int $levels = null): FsRestrictions
    {
        if (!$levels) {
            $levels = 1;
        }
        $restrictions = FsRestrictions::new()
                                      ->setLabel($this->label);
        foreach ($this->source as $directory => $write) {
            // Negative level will calculate in reverse
            if (!$levels) {
                throw new OutOfBoundsException(tr('Invalid level ":level" specified, must be a positive or negative integer, and cannot be 0', [
                    ':level' => $levels,
                ]));
            }
            if ($levels > 0) {
                $parent = Strings::until($directory, '/', $levels);
            } else {
                $count  = FsPath::countDirectories($directory);
                $parent = Strings::until($directory, '/', $count + $levels);
            }
            $restrictions->addDirectory($parent, $write);
        }

        return $restrictions;
    }


    /**
     * Add new directory for this restriction
     *
     * @param Stringable|string $directory
     * @param bool              $write
     *
     * @return static
     */
    public function addDirectory(Stringable|string $directory, bool $write = false): static
    {
        $this->source[FsPath::absolutePath($directory, null, false)] = $write;

        return $this;
    }


    /**
     * Returns a restrictions object with the current directory and the specified child directory attached
     *
     * This is useful when we want more strict restrictions
     *
     * @param string|array $child_directories
     * @param bool|null    $write
     *
     * @return FsRestrictions
     */
    public function getChild(string|array $child_directories, ?bool $write = null): FsRestrictions
    {
        $restrictions      = FsRestrictions::new()
                                           ->setLabel($this->label);
        $child_directories = Arrays::force($child_directories);
        foreach ($this->source as $directory => $original_write) {
            foreach ($child_directories as $child) {
                $restrictions->addDirectory(Strings::slash($directory) . Strings::ensureStartsNotWith($child, '/'), $write ?? $original_write);
            }
        }

        return $restrictions;
    }


    /**
     * Clear all directories for this restriction
     *
     * @return static
     */
    public function clearDirectories(): static
    {
        $this->source = [];

        return $this;
    }


    /**
     * Set all directories for this restriction
     *
     * @param Stringable|array|string $directories
     * @param bool                    $write
     *
     * @return static
     */
    public function addDirectories(Stringable|array|string $directories, bool $write = false): static
    {
        foreach (Arrays::force($directories) as $directory => $directory_write) {
            if (is_numeric($directory)) {
                // Directory array was not specified as [directory => write, directory => write, ...] but as
                // [directory, directory, ...]
                // Get the correct directory names and use the "global" $write flag instead
                $directory       = $directory_write;
                $directory_write = $write;
            }
            if (is_array($directory)) {
                $this->addDirectories($directories, $directory_write);
            } else {
                $this->addDirectory($directory, $directory_write);
            }
        }

        return $this;
    }


    /**
     * Returns all directories for this restriction
     *
     * @return array
     */
    public function getSource(): array
    {
        return $this->source;
    }


    /**
     * Set all directories for this restriction
     *
     * @param Stringable|array|string $directories
     * @param bool                    $write
     *
     * @return static
     */
    public function setSource(Stringable|array|string $directories, bool $write = false): static
    {
        $this->source = [];

        return $this->addDirectories($directories, $write);
    }


    /**
     * Sets the restrictions label only if the specified label is not empty, and this object's label is NULL or "system"
     *
     * @param string|null $label
     *
     * @return $this
     */
    public function ensureLabel(?string $label): static
    {
        if ($label and (empty($this->label) or ($this->label === tr('Unspecified')))) {
            return $this->setLabel($label);
        }

        return $this;
    }


    /**
     * Returns the label for this restriction
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }


    /**
     * Sets the label for this restriction
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabel(?string $label): static
    {
        $this->label = (get_null($label) ?? tr('Unspecified'));

        return $this;
    }


    /**
     * @param Stringable|array|string $patterns
     * @param bool                    $write
     *
     * @return void
     */
    public function check(Stringable|array|string &$patterns, bool $write): void
    {
        if (!$this->source) {
            throw new RestrictionsException(tr('The ":label" restrictions have no directories specified', [
                ':label' => $this->label,
            ]));
        }

        // Check each specified directory pattern to see if its allowed or restricted
        foreach (Arrays::force($patterns) as &$pattern) {
            foreach ($this->source as $path => $restrict_write) {
                $path    = FsPath::absolutePath($path, null, false);
                $pattern = FsPath::absolutePath($pattern, null, false);

                if (str_starts_with($pattern, Strings::ensureEndsNotWith($path, '/'))) {
                    if ($write and !$restrict_write) {
                        throw RestrictionsException::new(tr('Write access to directory patterns ":patterns" denied by ":label" restrictions', [
                            ':patterns' => $pattern,
                            ':label'    => $this->label,
                        ]))->addData([
                            'label'    => $this->label,
                            'patterns' => $patterns,
                            'paths'    => $this->source,
                        ]);
                    }

                    // Access ok!
                    return;
                }
            }

            // The specified pattern(s) are not allowed by the specified restrictions
            throw RestrictionsException::new(tr(':method access to requested directory patterns ":patterns" denied due to restrictions defined by ":label"', [
                ':method'   => $write ? tr('Write') : tr('Read'),
                ':patterns' => $pattern,
                ':label'    => $this->label,
            ]))->addData([
                'label'    => $this->label,
                'patterns' => $patterns,
                'paths'    => $this->source,
            ]);
        }
    }


    /**
     * Return these restrictions but with write enabled
     *
     * @return FsRestrictionsInterface
     */
    public function getTheseWritable(): FsRestrictionsInterface
    {
        $restrictions = new FsRestrictions();

        foreach ($this->source as $path => $write) {
            $restrictions->addDirectory($path, true);
        }

        return $restrictions;
    }
}

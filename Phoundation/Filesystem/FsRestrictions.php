<?php

/**
 * FsRestrictions class
 *
 * This class manages file access restrictions
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use PDOStatement;
use Phoundation\Cli\CliCommand;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataSourceArray;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Filesystem\Exception\WriteRestrictionsException;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Web;
use Stringable;
use Throwable;


class FsRestrictions implements FsRestrictionsInterface
{
    use TraitDataSourceArray;


    /**
     * FsRestrictions name
     *
     * @var string $label
     */
    protected string $label;

    /**
     * Contains the source arrays but in ordered fashion from longest to shortest
     *
     * @var array $ordered
     */
    protected array $ordered;


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
            $this->label = $call?->getCall() ?? tr('unknown');

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
            foreach (Arrays::force($directories) as $directory) {
                $this->addDirectory($directory, $write);
            }
        }
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
     * Returns a new writable FsRestrictions object with the specified restrictions
     *
     * @param Stringable|string|array|null $directories
     * @param string|null                  $label
     *
     * @return static
     */
    public static function newWritable(Stringable|string|array|null $directories = null, ?string $label = null): static
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
    public static function newReadonly(Stringable|string|array|null $directories = null, ?string $label = null): static
    {
        return new static($directories, false, $label);
    }


    /**
     * Returns a restrictions object for DIRECTORY_ROOT
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newRoot(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_ROOT . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for /
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newFilesystemRoot(bool $write = false, ?string $sub_directory = null): static
    {
        return new static('/' . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for DIRECTORY_DATA
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newData(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_DATA . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for DIRECTORY_SYSTEM
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newSystem(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_SYSTEM . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for DIRECTORY_SYSTEM/cache
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newCache(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_SYSTEM . 'cache/' . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for DIRECTORY_TMP
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newTemporary(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_TMP . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for DIRECTORY_PUBTMP
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newPublicTemporary(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_PUBTMP . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for DIRECTORY_COMMANDS
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newCommands(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_COMMANDS . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for DIRECTORY_HOOKS
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newHooks(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_HOOKS . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for DIRECTORY_WEB
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return FsRestrictions
     */
    public static function newWeb(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_WEB . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for DIRECTORY_CDN
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newCdn(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_CDN . $sub_directory, $write);
    }


    /**
     * Returns a restrictions object for DIRECTORY_DATA/files/
     *
     * @param bool        $write
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newUserFilesObject(bool $write = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_DATA . 'files/' . $sub_directory, $write);
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

        return static::newSystem();
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
     * Return the directories for this FsRestrictions object in string format
     *
     * @return string
     */
    public function __toString(): string
    {
        return implode(',', array_keys($this->source));
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

        if ($levels < 1) {
            throw new OutOfBoundsException(tr('Invalid parent level ":level" specified, must be 1 or higher', [
                ':level' => $levels
            ]));
        }

        $restrictions = FsRestrictions::new()->setLabel($this->label);

        foreach ($this->source as $directory => $write) {
            for ($l = 0; $l < $levels; $l++) {
                $directory = dirname($directory);
            }

            $restrictions->addDirectory($directory, $write);
        }

        return $restrictions;
    }


    /**
     * Add new directory for this restriction
     *
     * @param Stringable|string|null $directory
     * @param bool                   $write
     *
     * @return static
     */
    public function addDirectory(Stringable|string|null $directory, bool $write = false): static
    {
        if ($directory) {
            $this->source[FsPath::absolutePath($directory, null, false)] = $write;
        }

        unset($this->ordered);
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
        $restrictions      = FsRestrictions::new()->setLabel($this->label);
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
     * Adds restrictions from the specified restrictions object to these restrictions
     *
     * @param FsRestrictionsInterface|null $restrictions
     *
     * @return static
     */
    public function addRestrictions(?FsRestrictionsInterface $restrictions): static
    {
        if ($restrictions) {
            return $this->addLabel($restrictions->getLabel())
                        ->addDirectories($restrictions?->getSource());
        }

        return $this;
    }


    /**
     * Set all directories for this restriction
     *
     * @param Stringable|array|string|null $directories
     *
     * @return static
     */
    public function addDirectories(Stringable|array|string|null $directories): static
    {
        if ($directories) {
            foreach (Arrays::force($directories) as $directory => $directory_write) {
                $this->addDirectory($directory, $directory_write);
            }
        }

        return $this;
    }


    /**
     * Set all directories for this restriction
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
    {
        $this->source = [];

        return $this->addDirectories(Arrays::extractSourceArray($source, $execute));
    }


    /**
     * Sets the restrictions label only if the specified label is not empty, and this object's label is NULL or "system"
     *
     * @param string|null $label
     *
     * @return static
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
     * Adds the specified label for this restriction
     *
     * @param string|null $label
     *
     * @return static
     */
    public function addLabel(?string $label): static
    {
        $this->label .= ', ' . (get_null($label) ?? tr('Unspecified'));

        return $this;
    }


    /**
     * @param Stringable|string $pattern
     * @param bool              $write
     * @param Throwable|null    $e
     *
     * @return void
     *
     * @throws WriteRestrictionsException|RestrictionsException
     */
    public function check(Stringable|string $pattern, bool $write, ?Throwable $e = null): void
    {
        switch ($this->isRestricted($pattern, $write, $e)) {
            case false:
                return;

            case 'pattern':
                // The specified pattern(s) are not allowed by the specified restrictions
                throw RestrictionsException::new(tr(':method access to requested directory pattern ":pattern" denied due to restrictions defined by ":label"', [
                    ':method'  => $write ? tr('Write') : tr('Read'),
                    ':pattern' => $pattern,
                    ':label'   => $this->label,
                ]), $e)->addData([
                    'label'   => $this->label,
                    'pattern' => $pattern,
                    'paths'   => $this->source,
                ]);

            case 'write':
                throw WriteRestrictionsException::new(tr('Write access to directory pattern ":pattern" denied by ":label" readonly restrictions', [
                        ':pattern' => $pattern,
                        ':label'   => $this->label,
                    ]), $e)->addData([
                        'label'   => $this->label,
                        'pattern' => $pattern,
                        'paths'   => $this->source,
                    ]);
        }
    }


    /**
     * Ensures the $this->ordered array is available
     *
     * @return void
     */
    protected function ensureOrdered(): void
    {
        if (empty($this->ordered)) {
            $this->order();
        }
    }


    /**
     * Rebuilds the $this->ordered array
     *
     * @return void
     */
    protected function order(): void
    {
        $this->ordered = $this->source;

        uksort($this->ordered, function ($a, $b) {
            if (strlen($a) < strlen($b)) {
                return 1;
            }

            if (strlen($a) > strlen($b)) {
                return -1;
            }

            return 0;
        });
    }


    /**
     * Returns true if access to the specified pattern is restricted by this object
     *
     * @param Stringable|string $pattern
     * @param bool              $write
     * @param Throwable|null    $e
     *
     * @return false|string
     */
    public function isRestricted(Stringable|string $pattern, bool $write, ?Throwable $e = null): false|string
    {
        if (!$this->source) {
            throw new RestrictionsException(tr('The ":label" restrictions have no paths specified', [
                ':label' => $this->label,
            ]), $e);
        }

        $this->ensureOrdered();

        // Check each specified directory pattern to see if its allowed or restricted
        foreach ($this->ordered as $path => $allow_write) {
            $path    = FsPath::absolutePath($path   , null, false);
            $pattern = FsPath::absolutePath($pattern, null, false);

            if (str_starts_with($pattern, Strings::ensureEndsNotWith($path, '/'))) {
                if ($write and !$allow_write) {
                    return 'write';
                }

                // Access is NOT restricted!
                return false;
            }
        }

        return 'pattern';
    }


    /**
     * Return these restrictions but with write enabled
     *
     * @return FsRestrictionsInterface
     */
    public function makeWritable(): FsRestrictionsInterface
    {
        $restrictions = new FsRestrictions();

        foreach ($this->source as $path => $write) {
            $restrictions->addDirectory($path, true);
        }

        return $restrictions;
    }
}

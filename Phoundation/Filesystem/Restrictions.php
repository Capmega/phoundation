<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


/**
 * Restrictions class
 *
 * This class manages file access restrictions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Restrictions implements RestrictionsInterface
{
    /**
     * Internal store of all restrictions
     *
     * @var array $source
     */
    protected array $source = [];

    /**
     * Restrictions name
     *
     * @var string $label
     */
    protected string $label = 'system';


    /**
     * Restrictions constructor
     *
     * @param Stringable|string|array|null $directories
     * @param bool $write
     * @param string|null $label
     */
    public function __construct(Stringable|string|array|null $directories = null, bool $write = false, ?string $label = null)
    {
        if ($label) {
            $this->label = $label;
        }

        if ($directories) {
            $this->setSource($directories, $write);
        }
    }


    /**
     * Return the directories for this Restrictions object in string format
     *
     * @return string
     */
    public function __toString(): string
    {
        return implode(',', array_keys($this->source));
    }


    /**
     * Return the directories for this Restrictions object
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->source;
    }


    /**
     * Returns a new Restrictions object with the specified restrictions
     *
     * @param Stringable|string|array|null $directories
     * @param bool $write
     * @param string|null $label
     * @return static
     */
    public static function new(Stringable|string|array|null $directories = null, bool $write = false, ?string $label = null): static
    {
        return new static($directories, $write, $label);
    }


    /**
     * Returns a new writable Restrictions object with the specified restrictions
     *
     * @param Stringable|string|array|null $directories
     * @param string|null $label
     * @return static
     */
    public static function writable(Stringable|string|array|null $directories = null, ?string $label = null): static
    {
        return new static($directories, true, $label);
    }


    /**
     * Returns a new readonly Restrictions object with the specified restrictions
     *
     * @param Stringable|string|array|null $directories
     * @param string|null $label
     * @return static
     */
    public static function readonly(Stringable|string|array|null $directories = null, ?string $label = null): static
    {
        return new static($directories, false, $label);
    }


    /**
     * Returns the default restrictions object if the given specified restrictions are empty
     *
     * @param RestrictionsInterface|null ...$restrictions
     * @return static
     */
    public static function default(RestrictionsInterface|null ...$restrictions): static
    {
        $restriction = null;

        // Find the first restrictions object
        foreach ($restrictions as $restriction) {
            if ($restriction) {
                break;
            }
        }

        return static::ensure($restriction);
    }


    /**
     * Returns either the specified restrictions object or the Core restrictions object
     *
     * With this, availability of restrictions is guaranteed, even if a function did not receive restrictions. If Core
     * restrictions are returned, these core restrictions are the ones that apply
     *
     * @param RestrictionsInterface|array|string|null $restrictions  The restriction data that must be ensured to be a
     *                                                      Restrictions object
     * @param bool $write                                   If $restrictions is not specified as a Restrictions class,
     *                                                      but as a directory string, or array of directory strings,
     *                                                      then this method will convert that into a Restrictions
     *                                                      object and this is the $write modifier for that object
     * @param string|null $label                            If $restrictions is not specified as a Restrictions class,
     *                                                      but as a directory string, or array of directory strings,
     *                                                      then this method will convert that into a Restrictions
     *                                                      object and this is the $label modifier for that object
     * @return Restrictions                                 A Restrictions object. If possible, the specified
     *                                                      restrictions will be returned but if no $restictions were
     *                                                      specified ($restrictions was null or an empty string), the
     *                                                      Core restrictions will be returned instead
     */
    public static function ensure(RestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): RestrictionsInterface
    {
        if ($restrictions) {
            if (!is_object($restrictions)) {
                // Restrictions were specified by simple directory string or array of directories. Convert to restrictions object
                $restrictions = new Restrictions($restrictions, $write, $label);
            }

            return $restrictions;
        }

        return static::getSystem();
    }


    /**
     * Returns a restrictions object with parent directories for all directories in this restrictions object
     *
     * This is useful for the Directory object where one will want to be able to access or create the parent directory of the file
     * that needs to be accessed
     *
     * @param int|null $levels
     * @return Restrictions
     */
    public function getParent(?int $levels = null): Restrictions
    {
        if (!$levels) {
            $levels = 1;
        }

        $restrictions = Restrictions::new()->setLabel($this->label);

        foreach ($this->source as $directory => $write) {
            // Negative level will calculate in reverse
            if (!$levels) {
                throw new OutOfBoundsException(tr('Invalid level ":level" specified, must be a positive or negative integer, and cannot be 0', [
                    ':level' => $levels
                ]));
            }

            if ($levels > 0) {
                $parent = Strings::until($directory, '/', $levels);

            } else {
                $count  = Path::countDirectories($directory);
                $parent = Strings::until($directory, '/', $count + $levels);
            }

            $restrictions->addDirectory($parent, $write);
        }

        return $restrictions;
    }


    /**
     * Returns a restrictions object with the current directory and the specified child directory attached
     *
     * This is useful when we want more strict restrictions
     *
     * @param string|array $child_directories
     * @param bool|null $write
     * @return Restrictions
     */
    public function getChild(string|array $child_directories, ?bool $write = null): Restrictions
    {
        $restrictions = Restrictions::new()->setLabel($this->label);
        $child_directories  = Arrays::force($child_directories);

        foreach ($this->source as $directory => $original_write) {
            foreach ($child_directories as $child) {
                $restrictions->addDirectory(Strings::slash($directory) . Strings::startsNotWith($child, '/'), $write ?? $original_write);
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
     * @param bool $write
     * @return static
     */
    public function setSource(Stringable|array|string $directories, bool $write = false): static
    {
        $this->source = [];
        return $this->addDirectories($directories, $write);
    }


    /**
     * Set all directories for this restriction
     *
     * @param Stringable|array|string $directories
     * @param bool $write
     * @return static
     */
    public function addDirectories(Stringable|array|string $directories, bool $write = false): static
    {
        foreach (Arrays::force($directories) as $directory => $directory_write) {
            if (is_numeric($directory)) {
                // Directory array was not specified as [directory => write, directory => write, ...] but as
                // [directory, directory, ...]
                // Get the correct directory names and use the "global" $write flag instead
                $directory  = $directory_write;
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
     * Add new directory for this restriction
     *
     * @param Stringable|string $directory
     * @param bool $write
     * @return static
     */
    public function addDirectory(Stringable|string $directory, bool $write = false): static
    {
        $this->source[Path::getAbsolute($directory, null, false)] = $write;
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
     * Sets the label for this restriction
     *
     * @param string|null $label
     * @return static
     */
    public function setLabel(?string $label): static
    {
        $this->label = ($label ?? 'system');
        return $this;
    }


    /**
     * Sets the restrictions label only if the specified label is not empty, and this object's label is NULL or "system"
     *
     * @param string|null $label
     * @return $this
     */
    public function ensureLabel(?string $label): static
    {
        if ($label and (empty($this->label) or $this->label === 'system')) {
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
     * @param Stringable|array|string $patterns
     * @param bool $write
     * @return void
     */
    public function check(Stringable|array|string &$patterns, bool $write): void
    {
        if (!$this->source) {
            throw new RestrictionsException(tr('The ":label" restrictions have no directories specified', [
                ':label' => $this->label
            ]));
        }

        // Check each specified directory pattern to see if its allowed or restricted
        foreach (Arrays::force($patterns) as &$pattern) {
            foreach ($this->source as $directory => $restrict_write) {
                $directory    = Path::getAbsolute($directory   , null, false);
                $pattern = Path::getAbsolute($pattern, null, false);

                if (str_starts_with($pattern, $directory)) {
                    if ($write and !$restrict_write) {
                        throw RestrictionsException::new(tr('Write access to directory patterns ":patterns" denied by ":label" restrictions', [
                            ':patterns' => $pattern,
                            ':label'    => $this->label
                        ]))->addData([
                            'label'    => $this->label,
                            'patterns' => $patterns,
                            'directories'    => $this->source
                        ]);
                    }

                    // Access ok!
                    return;
                }
            }

            // The specified pattern(s) are not allowed by the specified restrictions
            throw RestrictionsException::new(tr('Access to requested directory patterns ":patterns" denied by ":label" restrictions', [
                ':patterns' => $pattern,
                ':label'    => $this->label
            ]))->addData([
                'label'       => $this->label,
                'patterns'    => $patterns,
                'directories' => $this->source
            ]);
        }
    }


    /**
     * Returns system general file access restrictions
     *
     * @return RestrictionsInterface
     */
    public static function getSystem(): RestrictionsInterface
    {
        static $restrictions;

        if (empty($restrictions)) {
            $restrictions = Restrictions::new(DIRECTORY_DATA, false, 'System');
        }

        return $restrictions;
    }


    /**
     * Return these restrictions but with write enabled
     *
     * @return RestrictionsInterface
     */
    public function getWritable(): RestrictionsInterface
    {
        $restrictions = new Restrictions();

        foreach ($this->source as $path => $write) {
            $restrictions->addDirectory($path, true);
        }

        return $restrictions;
    }
}

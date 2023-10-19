<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Stringable;


/**
 * Restrictions class
 *
 * This class manages file access restrictions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Restrictions implements RestrictionsInterface
{
    /**
     * Internal store of all restrictions
     *
     * @var array $paths
     */
    protected array $paths = [];

    /**
     * Restrictions name
     *
     * @var string $label
     */
    protected string $label = 'system';


    /**
     * Restrictions constructor
     *
     * @param Stringable|string|array|null $paths
     * @param bool $write
     * @param string|null $label
     */
    public function __construct(Stringable|string|array|null $paths = null, bool $write = false, ?string $label = null)
    {
        if ($label) {
            $this->label = $label;
        }

        if ($paths) {
            $this->setPaths($paths, $write);
        }
    }


    /**
     * Return the paths for this Restrictions object in string format
     *
     * @return string
     */
    public function __toString(): string
    {
        return implode(',', array_keys($this->paths));
    }


    /**
     * Return the paths for this Restrictions object
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->paths;
    }


    /**
     * Returns a new Restrictions object with the specified restrictions
     *
     * @param Stringable|string|array|null $paths
     * @param bool $write
     * @param string|null $label
     * @return static
     */
    public static function new(Stringable|string|array|null $paths = null, bool $write = false, ?string $label = null): static
    {
        return new static($paths, $write, $label);
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
     *                                                      but as a path string, or array of path strings, then this
     *                                                      method will convert that into a Restrictions object and this
     *                                                      is the $write modifier for that object
     * @param string|null $label                            If $restrictions is not specified as a Restrictions class,
     *                                                      but as a path string, or array of path strings, then this
     *                                                      method will convert that into a Restrictions object and this
     *                                                      is the $label modifier for that object
     * @return Restrictions                                 A Restrictions object. If possible, the specified
     *                                                      restrictions will be returned but if no $restictions were
     *                                                      specified ($restrictions was null or an empty string), the
     *                                                      Core restrictions will be returned instead
     */
    public static function ensure(RestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): RestrictionsInterface
    {
        if ($restrictions) {
            if (!is_object($restrictions)) {
                // Restrictions were specified by simple path string or array of paths. Convert to restrictions object
                $restrictions = new Restrictions($restrictions, $write, $label);
            }

            return $restrictions;
        }

        return static::getSystem();
    }


    /**
     * Returns a restrictions object with parent paths for all paths in this restrictions object
     *
     * This is useful for the Path object where one will want to be able to access or create the parent path of the file
     * that needs to be accessed
     *
     * @return Restrictions
     */
    public function getParent(): Restrictions
    {
        $restrictions = Restrictions::new()->setLabel($this->label);

        foreach ($this->paths as $path => $write) {
            $restrictions->addPath(dirname($path), $write);
        }

        return $restrictions;
    }


    /**
     * Returns a restrictions object with the current path and the specified child path attached
     *
     * This is useful when we want more strict restrictions
     *
     * @param string|array $child_paths
     * @param bool|null $write
     * @return Restrictions
     */
    public function getChild(string|array $child_paths, ?bool $write = null): Restrictions
    {
        $restrictions = Restrictions::new()->setLabel($this->label);
        $child_paths  = Arrays::force($child_paths);

        foreach ($this->paths as $path => $original_write) {
            foreach ($child_paths as $child) {
                $restrictions->addPath(Strings::slash($path) . Strings::startsNotWith($child, '/'), $write ?? $original_write);
            }
        }

        return $restrictions;
    }


    /**
     * Clear all paths for this restriction
     *
     * @return static
     */
    public function clearPaths(): static
    {
        $this->paths = [];
        return $this;
    }


    /**
     * Set all paths for this restriction
     *
     * @param Stringable|array|string $paths
     * @param bool $write
     * @return static
     */
    public function setPaths(Stringable|array|string $paths, bool $write = false): static
    {
        $this->paths = [];
        return $this->addPaths($paths, $write);
    }


    /**
     * Set all paths for this restriction
     *
     * @param Stringable|array|string $paths
     * @param bool $write
     * @return static
     */
    public function addPaths(Stringable|array|string $paths, bool $write = false): static
    {
        foreach (Arrays::force($paths) as $path => $path_write) {
            if (is_numeric($path)) {
                // Path array was not specified as [path => write, path => write, ...] but as [path, path, ...]
                // Get the correct path names and use the "global" $write flag instead
                $path       = $path_write;
                $path_write = $write;
            }

            if (is_array($path)) {
                $this->addPaths($paths, $path_write);

            } else {
                $this->addPath($path, $path_write);
            }
        }

        return $this;
    }


    /**
     * Add new path for this restriction
     *
     * @param Stringable|string $path
     * @param bool $write
     * @return static
     */
    public function addPath(Stringable|string $path, bool $write = false): static
    {
        $this->paths[Filesystem::absolute($path, null, false)] = $write;
        return $this;
    }


    /**
     * Returns all paths for this restriction
     *
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
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
        if (!$this->paths) {
            throw new RestrictionsException(tr('The ":label" restrictions have no paths specified', [
                ':label' => $this->label
            ]));
        }

        // Check each specified path pattern to see if its allowed or restricted
        foreach (Arrays::force($patterns) as &$pattern) {
            foreach ($this->paths as $path => $restrict_write) {
                $path    = Filesystem::absolute($path   , null, false);
                $pattern = Filesystem::absolute($pattern, null, false);

                if (str_starts_with($pattern, $path)) {
                    if ($write and !$restrict_write) {
                        throw RestrictionsException::new(tr('Write access to path patterns ":patterns" denied by ":label" restrictions', [
                            ':patterns' => $pattern,
                            ':label'    => $this->label
                        ]))->addData([
                            'label'    => $this->label,
                            'patterns' => $patterns,
                            'paths'    => $this->paths
                        ]);
                    }

                    // Access ok!
                    return;
                }
            }

            // The specified pattern(s) are not allowed by the specified restrictions
            throw RestrictionsException::new(tr('Access to requested path patterns ":patterns" denied by ":label" restrictions', [
                ':patterns' => $pattern,
                ':label'    => $this->label
            ]))->addData([
                'label'    => $this->label,
                'patterns' => $patterns,
                'paths'    => $this->paths
            ])->makeWarning();
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
            $restrictions = Restrictions::new(PATH_DATA, false, 'System');
        }

        return $restrictions;
    }
}

<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Stringable;


/**
 * Restrictions class
 *
 * This class manages file access restrictions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Restrictions
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
        foreach (Arrays::force($paths) as $path) {
            $this->addPath($path, $write);
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
     * Returns the label for this restriction
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }


    /**
     * @param string|array $patterns
     * @param bool $write
     * @return void
     */
    public function check(string|array &$patterns, bool $write): void
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
                        ]))->setData([
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
            throw RestrictionsException::new(tr('Access to path patterns ":patterns" denied by ":label" restrictions', [
                ':patterns' => $pattern,
                ':label'    => $this->label
            ]))->setData([
                'label'    => $this->label,
                'patterns' => $patterns,
                'paths'    => $this->paths
            ])->makeWarning();
        }
    }
}
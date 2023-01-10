<?php

namespace Phoundation\Filesystem;

use Phoundation\Core\Arrays;
use Phoundation\Filesystem\Exception\RestrictionsException;



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
     * @param string|array|null $paths
     * @param bool $write
     * @param string|null $label
     */
    public function __construct(string|array|null $paths, bool $write = false, ?string $label = null)
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
     * @param string|array|null $paths
     * @param bool $write
     * @param string|null $label
     * @return static
     */
    public static function new(string|array|null $paths, bool $write = false, ?string $label = null): static
    {
        return new Restrictions($paths, $write, $label);
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
     * @param array|string $paths
     * @param bool $write
     * @return static
     */
    public function setPaths(array|string $paths, bool $write = false): static
    {
        $this->paths = [];
        return $this->addPaths($paths, $write);
    }



    /**
     * Set all paths for this restriction
     *
     * @param array|string $paths
     * @param bool $write
     * @return static
     */
    public function addPaths(array|string $paths, bool $write = false): static
    {
        foreach (Arrays::force($paths) as $path) {
            $this->addPath($path, $write);
        }

        return $this;
    }



    /**
     * Add new path for this restriction
     *
     * @param string $path
     * @param bool $write
     * @return static
     */
    public function addPath(string $path, bool $write = false): static
    {
        $this->paths[$path] = $write;
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
                        throw RestrictionsException::new(tr('Write access to path ":path" denied by ":label" restrictions', [
                            ':path'  => $pattern,
                            ':label' => $this->label
                        ]))->makeWarning();
                    }

                    // Access ok!
                    return;
                }
            }

            // The specified pattern(s) are not allwed by the specified restrictions
            throw RestrictionsException::new(tr('Access to path ":path" denied by ":label" restrictions', [
                ':path'  => $pattern,
                ':label' => $this->label
            ]))->makeWarning();
        }
    }
}
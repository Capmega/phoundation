<?php

namespace Phoundation\Filesystem;




use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\UnderConstructionException;
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
    protected string $label = 'undefined';



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
     * Set all paths for this restriction
     *
     * @param array|string $paths
     * @param bool $write
     * @return Restrictions
     */
    public function setPaths(array|string $paths, bool $write = false): Restrictions
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
     * @return Restrictions
     */
    public function addPath(string $path, bool $write = false): Restrictions
    {
        $this->paths[$path] = $write;
        return $this;
    }



    /**
     * @param string|array $patterns
     * @param bool $write
     * @return void
     */
    public function check(string|array $patterns, bool $write): void
    {
        if (!$this->paths) {
            throw new RestrictionsException(tr('The ":label" restrictions have no paths specified', [
                ':label' => $this->label
            ]));
        }

        // Check each specified path pattern to see if its allowed or restricted
        foreach (Arrays::force($patterns) as $pattern) {
            foreach ($this->paths as $path => $restrict_write) {
                $path = Path::absolute($path);

                if (str_starts_with($pattern, $path)) {
                    if ($write and !$restrict_write) {
                        throw Exceptions::RestrictionsException(tr('Write access to path ":path" denied by ":label" restrictions', [
                            ':path'  => $pattern,
                            ':label' => $this->label
                        ]))->makeWarning();
                    }

                    // Access ok!
                    return;
                }
            }

            // The specified pattern(s) are not allwed by the specified restrictions
            throw Exceptions::RestrictionsException(tr('Access to path ":path" denied by ":label" restrictions', [
                ':path'  => $pattern,
                ':label' => $this->label
            ]))->makeWarning();
        }
    }
}
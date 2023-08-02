<?php

declare(strict_types=1);

namespace Phoundation\Core\Hooks;


use Phoundation\Core\Arrays;
use Phoundation\Filesystem\File;

/**
 * Hook class
 *
 * This class can manage and (attempt to) execute specified hook scripts.
 *
 * Hook scripts are optional scripts that will be executed if they exist. Hook scripts are located in
 * PATH_ROOT/scripts/hooks/HOOK and PATH_ROOT/scripts/hooks/CLASS/HOOK. CLASS is an identifier for multiple hook scripts
 * that all have to do with the same system, to group them together. HOOK is the script to be executed
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Hook
{
    /**
     * The class of hooks that will be executed
     *
     * @var string|null $class
     */
    protected ?string $class;

    /**
     * The place where all hook scripts live
     *
     * @var string $path
     */
    protected string $path = PATH_ROOT . 'scripts/hooks';


    /**
     * Hook class constructor
     *
     * @param string|null $class
     */
    public function __construct(?string $class = null)
    {
        $this->class = $class;
    }


    /**
     * Returns a new Hook object
     *
     * @param string|null $class
     * @return static
     */
    public static function new(?string $class = null): static
    {
        return new static($class);
    }


    /**
     * Attempts to execute the specified hooks
     *
     * @param array|string $hooks
     * @return $this
     */
    public function execute(array|string $hooks): static
    {
        foreach (Arrays::force($hooks) as $hook) {
            $file = $this->path . $hook;
            File::new($file, $this->path)->checkReadable();

            include($file);
        }

        return $this;
    }
}
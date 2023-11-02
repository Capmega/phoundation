<?php

declare(strict_types=1);

namespace Phoundation\Core\Hooks;

use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\File;
use Throwable;


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
        $this->class = Strings::endsNotWith(trim($class), '/') . '/';

        if ($this->class) {
            $this->path .= $this->class;
        }
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

            if (!file_exists($file)) {
                // Only execute existing files
                continue;
            }

            // Ensure its readable, not a path, within the filesystem restrictions, etc...
            File::new($file, $this->path)->checkReadable();

            // Try executing it!
            try {
                Log::action(tr('Executing hook ":hook"', [
                    ':hook' => $this->class . '/' . $hook
                ]));

                include($file);

            } catch (Throwable $e) {
                Log::error(tr('Hook ":hook" failed to execute with the following exception', [
                    ':hook' => $hook
                ]));

                Log::error($e);
            }
        }

        return $this;
    }
}
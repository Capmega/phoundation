<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\Interfaces\CommandInterface;
use Phoundation\Processes\ProcessCore;


/**
 * Class Command
 *
 * This class contains various easy-to-use and ready-to-go command line commands in static methods.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
abstract class Command extends ProcessCore implements CommandInterface
{
    /**
     * Command constructor.
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null $packages
     */
    public function __construct(RestrictionsInterface|array|string|null $restrictions = null, ?string $packages = null)
    {
        // Ensure that the run files directory is available
        Path::new(PATH_ROOT . 'data/run/', Restrictions::new(PATH_DATA . 'run', true))->ensure();

        $this->setRestrictions($restrictions);

        if ($packages) {
            $this->setPackages($packages);
        }
    }


    /**
     * Create a new process factory for a specific command
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null $packages
     * @return static
     */
    public static function new(RestrictionsInterface|array|string|null $restrictions = null, ?string $packages = null): static
    {
        return new static($restrictions, $packages);
    }
}

<?php

/**
 * Class PhoCommand
 *
 * This class is used to easily execute Phoundation commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataEnvironment;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Os\Processes\Commands\Interfaces\PhoInterface;
use Phoundation\Os\Processes\Exception\ProcessException;
use Phoundation\Os\Processes\WorkersCore;
use Phoundation\Utils\Arrays;


class Pho extends PhoCore
{
    /**
     * Pho class constructor.
     *
     * @param array|string|null    $commands
     * @param FsFileInterface|null $pho
     */
    public function __construct(array|string|null $commands, ?FsFileInterface $pho = null)
    {
        $this->init($commands, $pho);
    }


    /**
     * Create a new process factory for a specific Phoundation command
     *
     * @param string|null          $pho_command
     * @param FsFileInterface|null $pho
     *
     * @return static
     */
    public static function new(?string $pho_command = null, ?FsFileInterface $pho = null): static
    {
        return new static($pho_command, $pho);
    }
}

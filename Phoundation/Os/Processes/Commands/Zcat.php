<?php

/**
 * Class Zcat
 *
 * This class manages the "zcat" command
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\TraitDataFile;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;

class Zcat extends Command
{
    use TraitDataFile;

    /**
     * Zcat class constructor
     *
     * @param FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory
     * @param string|null                                       $operating_system
     * @param string|null                                       $packages
     */
    public function __construct(FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory = null, ?string $operating_system = null, ?string $packages = null)
    {
        parent::__construct($execution_directory, $operating_system, $packages);
        $this->setCommand('zcat');
    }


    /**
     * Builds and returns the command line that will be executed
     *
     * @param bool $background
     *
     * @return string
     */
    public function getFullCommandLine(bool $background = false): string
    {
        if ($this->cached_command_line) {
            return $this->cached_command_line;
        }
        $this->addArgument($this->file);

        return parent::getFullCommandLine($background);
    }


    /**
     * Cats the output unzipped to the specified output
     *
     * @param EnumExecuteMethod $method
     *
     * @return string|int|bool|array|null
     */
    public function execute(EnumExecuteMethod $method = EnumExecuteMethod::passthru): string|int|bool|array|null
    {
        return parent::execute($method);
    }
}

<?php

/**
 * Class Zcat
 *
 * This class manages the "zcat" command
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\TraitDataFile;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;


class Zcat extends Command
{
    use TraitDataFile;

    /**
     * Zcat class constructor
     *
     * @param PhoRestrictionsInterface|PhoDirectoryInterface|null $execution_directory
     * @param string|null                                         $operating_system
     * @param string|null                                         $packages
     */
    public function __construct(PhoRestrictionsInterface|PhoDirectoryInterface|null $execution_directory = null, ?string $operating_system = null, ?string $packages = null)
    {
        parent::__construct($execution_directory, $operating_system, $packages);
        $this->setCommand('zcat');
    }


    /**
     * Builds and returns the command line that will be executed
     *
     * @param bool $background
     * @param bool $pipe
     *
     * @return string
     */
    public function getFullCommandLine(bool $background = false, bool $pipe = false): string
    {
        if ($this->cached_command_line) {
            return $this->cached_command_line;
        }
        $this->addArgument($this->o_file);

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

<?php

/**
 * Class Df
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */

declare(strict_types=1);

namespace Phoundation\Filesystem\Commands;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataPath;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Processes\Commands\Command;
use Phoundation\Utils\Arrays;

class Df extends Command
{
    use TraitDataPath;


    /**
     * If true, will check inodes instead of byte size
     *
     * @var bool $check_inodes
     */
    protected bool $check_inodes = false;


    /**
     * Returns if this command checks inodes instead of byte size
     *
     * @return bool
     */
    public function getCheckInodes(): bool
    {
        return $this->check_inodes;
    }


    /**
     * Sets if this command checks inodes instead of byte size
     *
     * @param bool $check_inodes
     *
     * @return static
     */
    public function setCheckInodes(bool $check_inodes): static
    {
        $this->check_inodes = $check_inodes;
        return $this;
    }


    /**
     * Executes the df command
     *
     * @return array
     */
    public function executeReturnArray(): array
    {
        $this->setCommand('df');
        $this->addArgument('-T');

        if ($this->check_inodes) {
            $this->addArgument('-i');
        }

        if ($this->path) {
            $this->addArgument($this->path);
        }

        return parent::executeReturnArray();
    }


    /**
     * Returns the output of the DF command in a usable Iterator interface
     *
     * @return IteratorInterface
     */
    public function getResults(): IteratorInterface
    {
        $return = [];

        if ($this->check_inodes) {
            $results = Arrays::fromCsvSource($this->output, [
                'filesystem' => ' ',
                'type'       => ' ',
                'inodes'     => ' ',
                'iused'      => ' ',
                'ifree'      => ' ',
                'iuse'       => ' ',
                'mountedon'  => ' ',
            ], 'filesystem');

        } else {
            $results = Arrays::fromCsvSource($this->output, [
                'filesystem' => ' ',
                'type'       => ' ',
                'size'       => ' ',
                'used'       => ' ',
                'available'  => ' ',
                'use'        => ' ',
                'mountedon'  => ' ',
            ], 'filesystem');
        }

        foreach ($results as $filesystem => $result) {
            if (str_starts_with($filesystem, '/dev/')) {
                $filesystem = FsFile::new($filesystem, FsRestrictions::getReadonly('/dev/', 'Df::getResults()'))
                                    ->followLink(true)
                                    ->getPath();
            }

            $return[$filesystem] =  $result;
        }

        return new Iterator($return);
    }
}

<?php

/**
 * Class Branches
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Data\IteratorCore;
use Phoundation\Developer\Versioning\Git\Interfaces\BranchesInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Os\Processes\Process;


class Branches extends IteratorCore implements BranchesInterface
{
    use TraitGitProcess {
        setDirectory as protected setGitDirectory;
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param FsDirectoryInterface $directory
     *
     * @return static
     */
    public function setDirectory(FsDirectoryInterface $directory): static
    {
        $this->setGitDirectory($directory);

        $results = Process::new('git')
                          ->setExecutionDirectory(new FsDirectory(
                              $this->directory,
                              FsRestrictions::getWritable($this->directory, 'Branches::setPath()')
                          ))
                          ->addArgument('branch')
                          ->addArgument('--quiet')
                          ->addArgument('--no-color')
                          ->executeReturnArray();

        foreach ($results as $line) {
            if (str_starts_with($line, '*')) {
                $this->source[substr($line, 2)] = true;

            } else {
                $this->source[substr($line, 2)] = false;
            }
        }

        return $this;
    }


    /**
     * Display the branches on the CLI
     *
     * @return void
     */
    public function displayCliTable(): void
    {
        $list = [];

        foreach ($this->getSource() as $branch => $selected) {
            $list[$branch] = ['selected' => $selected ? '*' : ''];
        }

        Cli::displayTable($list, ['branch'   => tr('Branch'),
                                  'selected' => tr('Selected'),
        ], 'branch');
    }
}

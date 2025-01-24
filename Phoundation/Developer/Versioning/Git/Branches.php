<?php

/**
 * Class Branches
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Data\IteratorCore;
use Phoundation\Developer\Versioning\Git\Interfaces\BranchesInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Os\Processes\Process;


class Branches extends IteratorCore implements BranchesInterface
{
    use TraitGitProcess {
        setDirectory as protected setGitDirectory;
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param PhoDirectoryInterface $directory
     *
     * @return static
     */
    public function setDirectory(PhoDirectoryInterface $directory): static
    {
        $this->setGitDirectory($directory);

        $results = Process::new('git')
                          ->setExecutionDirectory(new PhoDirectory(
                              $this->directory,
                              PhoRestrictions::newWritable($this->directory)
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
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'branch'): static
    {
        $list = [];

        foreach ($this->getSource() as $branch => $selected) {
            $list[$branch] = ['selected' => $selected ? '*' : ''];
        }

        $filters = array_replace(
            $filters,
            [
                'branch'   => tr('Branch'),
                'selected' => tr('Selected'),
            ]
        );

        Cli::displayTable(
            $list,
            $filters,
            $id_column
        );

        return $this;
    }
}

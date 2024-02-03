<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Versioning\Git\Traits\GitProcess;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Os\Processes\Process;


/**
 * Class Branches
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Branches extends Iterator
{
    use GitProcess {
        setDirectory as protected setGitDirectory;
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param string $directory
     * @return static
     */
    public function setDirectory(string $directory): static
    {
        $this->setGitDirectory($directory);

        $results = Process::new('git')->setExecutionDirectory($this->directory)
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
    public function CliDisplayTable(): void
    {
        $list = [];

        foreach ($this->getSource() as $branch => $selected) {
            $list[$branch] = ['selected' => $selected ? '*' : ''];
        }

        Cli::displayTable($list, ['branch' => tr('Branch'), 'selected' => tr('Selected')], 'branch');
    }
}
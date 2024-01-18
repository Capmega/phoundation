<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Versioning\Git\Traits\GitProcess;
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
    use GitProcess;


    /**
     * Returns a list of all available branches for this GIT path
     *
     * @return array
     */
    public function getSource(): array
    {
        if (!$this->source) {
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
        }

        return $this->source;
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
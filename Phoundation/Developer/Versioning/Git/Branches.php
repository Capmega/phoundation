<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Data\Classes\Iterator;
use Phoundation\Developer\Versioning\Git\Traits\GitProcess;
use Phoundation\Processes\Process;


/**
 * Class Branches
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
    public function getList(): array
    {
        if (!$this->list) {
            $results = Process::new('git')
                ->setExecutionPath($this->path)
                ->addArgument('branch')
                ->addArgument('--quiet')
                ->addArgument('--no-color')
                ->executeReturnArray();

            foreach ($results as $line) {
                if (str_starts_with($line, '*')) {
                    $this->list[substr($line, 2)] = true;
                } else {
                    $this->list[substr($line, 2)] = false;
                }
            }
        }

        return $this->list;
    }



    /**
     * Display the branches on the CLI
     *
     * @return void
     */
    public function CliDisplayTable(): void
    {
        $list = [];

        foreach ($this->getList() as $branch => $selected) {
            $list[$branch] = ['selected' => $selected ? '*' : ''];
        }

        Cli::displayTable($list, ['branch' => tr('Branch'), 'selected' => tr('Selected')], 'branch');
    }
}
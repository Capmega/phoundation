<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Core\Log\Log;
use Phoundation\Developer\Versioning\Git\Traits\GitProcess;
use Phoundation\Developer\Versioning\Versioning;

/**
 * Class Stash
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Stash extends Versioning
{
    use GitProcess {
        setPath as protected setTraitPath;
    }


    /**
     * Unstashes the git changes
     *
     * @return static
     */
    public function stash(): static
    {
        $output = $this->git_process
            ->clearArguments()
            ->addArgument('stash')
            ->executeReturnArray();

        Log::notice($output, 4, false);

        return $this;
    }


    /**
     * Unstashes the git changes
     *
     * @return static
     */
    public function pop(): static
    {
        $output = $this->git_process
            ->clearArguments()
            ->addArgument('stash')
            ->addArgument('pop')
            ->executeReturnArray();

        Log::notice($output, 4, false);

        return $this;
    }


    /**
     * Lists the available stashes in the git repository
     *
     * @return array
     */
    public function getList(): array
    {
        $return  = [];
        $results = $this->git_process
            ->clearArguments()
            ->addArgument('stash')
            ->addArgument('list')
            ->executeReturnArray();

        foreach ($results as $result) {
            preg_match_all('/stash@\{(\d+)\}:\s(.+)/', $result, $matches);
            $return[$matches[0][0]] = $matches[0][1];
        }
        
        return $return;
    }


    /**
     * Lists the changes available in the top most stash in the git repository
     *
     * @return array
     */
    public function getShow(): array
    {
        return $this->git_process
            ->clearArguments()
            ->addArgument('stash')
            ->addArgument('show')
            ->executeReturnArray();
    }
}
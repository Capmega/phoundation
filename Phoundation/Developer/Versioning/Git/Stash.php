<?php

namespace Phoundation\Developer\Versioning\Git;

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
        $this->git
            ->clearArguments()
            ->addArgument('stash')
            ->executePassthru();

        return $this;
    }



    /**
     * Unstashes the git changes
     *
     * @return static
     */
    public function pop(): static
    {
        $this->git
            ->clearArguments()
            ->addArgument('stash')
            ->addArgument('pop')
            ->executePassthru();

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
        $results = $this->git
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
        return $this->git
            ->clearArguments()
            ->addArgument('stash')
            ->addArgument('show')
            ->executeReturnArray();
    }
}
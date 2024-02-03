<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Versioning\Git\Traits\GitProcess;
use Phoundation\Developer\Versioning\Versioning;


/**
 * Class Stash
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Stash extends Versioning
{
    use GitProcess {
        setDirectory as protected setTraitDirectory;
    }


    /**
     * Unstashes the git changes
     *
     * @param array|string|null $path
     * @return static
     */
    public function stash(array|string|null $path = null): static
    {
        $output = $this->git_process
            ->clearArguments()
            ->addArgument('stash')
            ->addArgument('--')
            ->addArguments($path)
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
     * @return IteratorInterface
     */
    public function getList(): IteratorInterface
    {
        $return  = [];
        $results = $this->git_process
            ->clearArguments()
            ->addArgument('stash')
            ->addArgument('list')
            ->executeReturnArray();

        foreach ($results as $result) {
            preg_match_all('/stash@\{(\d+)\}:\s(.+)/', $result, $matches);
            $return[$matches[0][0]] = $matches[2][0];
        }
        
        return new Iterator($return);
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
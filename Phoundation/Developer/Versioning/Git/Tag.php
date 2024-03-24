<?php

/**
 * Class Tag
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\TagInterface;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;
use Phoundation\Utils\Config;

class Tag implements TagInterface
{
    /**
     * The git process interface
     *
     * @var ProcessInterface $git
     */
    protected ProcessInterface $git;


    /**
     * Tag class constructor
     *
     * @param ProcessInterface $git
     */
    public function __construct(ProcessInterface $git)
    {
        $this->git = $git;
    }


    /**
     * Creates a new tag
     *
     * @param string $message
     * @param string $annotation
     * @param string|null $commit
     * @param bool|null $signed
     * @return $this
     */
    public function tag(string $message, string $annotation, ?string $commit = null, ?bool $signed = null): static
    {
        $signed = $signed ?? Config::getBoolean('versioning.git.sign', true);

        $this->git
            ->clearArguments()
            ->addArgument('tag')
            ->addArguments(['-m', $message])
            ->addArguments($annotation ? ['-a', $annotation] : null)
            ->addArguments($signed ? '-s' : null)
            ->addArguments($commit ?: null)
            ->executeNoReturn();

        return $this;
    }


    /**
     * Returns a list of all available tags
     *
     * @return IteratorInterface
     */
    public function list(): IteratorInterface
    {
        return $this->git
            ->clearArguments()
            ->addArgument('tag')
            ->executeReturnIterator();
    }


    /**
     * Pushes all local tags to the specified remote
     *
     * @param string $repository
     * @param string|null $branch
     * @return static
     */
    public function push(string $repository, ?string $branch = null): static
    {
        $output = $this->git
            ->clearArguments()
            ->addArgument('push')
            ->addArguments([$repository, $branch, '--tags'])
            ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Deletes the specified tag
     *
     * @param string $tag
     * @param string|null $remote
     * @return static
     */
    public function delete(string $tag, ?string $remote = null): static
    {
        if ($remote) {
            $this->git
                ->clearArguments()
                ->addArgument('push')
                ->addArguments([$remote, '--delete', $tag])
                ->executeNoReturn();

        } else {
            $this->git
                ->clearArguments()
                ->addArgument('tag')
                ->addArguments(['-d', $tag])
                ->executeNoReturn();
        }

        return $this;
    }
}

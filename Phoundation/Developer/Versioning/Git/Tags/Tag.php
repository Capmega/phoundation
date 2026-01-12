<?php

/**
 * Class Tag
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Tags;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Developer\Versioning\Git\Tags\Interfaces\TagInterface;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;


class Tag implements TagInterface
{
    /**
     * The git process interface
     *
     * @var ProcessInterface $o_git
     */
    protected ProcessInterface $o_git;


    /**
     * Tag class constructor
     *
     * @param ProcessInterface $git
     */
    public function __construct(ProcessInterface $git)
    {
        $this->o_git = $git;
    }


    /**
     * Creates a new tag
     *
     * @param string    $tag     The name for the annotated tag
     * @param string    $message The message for the annotated tag
     * @param bool|null $signed  If true, will sign the tag (requires git is configured to do so)
     *
     * @return static
     */
    public function tag(string $tag, string $message, ?bool $signed = null): static
    {
        $signed = $signed ?? config()->getBoolean('versioning.git.sign', true);

        $this->o_git->createTag();

        return $this;
    }


    /**
     * Returns a list of all available tags
     *
     * @return IteratorInterface
     */
    public function list(): IteratorInterface
    {
        return $this->o_git->clearArguments()
                           ->addArgument('tag')
                           ->executeReturnIterator();
    }


    /**
     * Deletes the specified tag
     *
     * @param string      $tag
     * @param string|null $remote
     *
     * @return static
     */
    public function delete(string $tag, ?string $remote = null): static
    {
        if ($remote) {
            $this->o_git->clearArguments()
                        ->addArgument('push')
                        ->addArguments([
                            $remote,
                            '--delete',
                            $tag,
                        ])
                        ->executeNoReturn();

        } else {
            $this->o_git->clearArguments()
                        ->addArgument('tag')
                        ->addArguments([
                            '-d',
                            $tag,
                        ])
                        ->executeNoReturn();
        }

        return $this;
    }
}

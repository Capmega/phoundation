<?php

/**
 * Class Tags
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

use Phoundation\Data\IteratorCore;
use Phoundation\Data\Traits\TraitStaticMethodNewWithRepository;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectRepository;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;


class Tags extends IteratorCore implements Interfaces\TagsInterface
{
    use TraitDataObjectRepository;
    use TraitStaticMethodNewWithRepository;


    /**
     * Tags class constructor
     *
     * @param RepositoryInterface $o_repository
     */
    public function __construct(RepositoryInterface $o_repository) {
        parent::__construct();
        $this->o_repository = $o_repository;

        $this->load();
    }


    /**
     * Loads the remotes for the specified git process
     *
     * @return static
     */
    public function load(): static
    {
        $this->source = $this->o_repository->getGitObject()->getTags();
        return $this;
    }


// Obsolete code, must be removed
//    /**
//     * Returns the directory for this ChangedFiles object
//     *
//     * @param PhoDirectoryInterface $directory
//     *
//     * @return static
//     */
//    public function setDirectory(PhoDirectoryInterface $directory): static
//    {
//        $this->setGitDirectory($directory);
//
//        $results = Process::new('git')
//                          ->setExecutionDirectory(new PhoDirectory(
//                              $this->o_path,
//                              PhoRestrictions::newWritableObject($this->o_path)
//                          ))
//                          ->addArgument('tag')
//                          ->addArgument('--quiet')
//                          ->addArgument('--no-color')
//                          ->executeReturnArray();
//
//        foreach ($results as $line) {
//            if (str_starts_with($line, '*')) {
//                $this->source[substr($line, 2)] = true;
//
//            } else {
//                $this->source[substr($line, 2)] = false;
//            }
//        }
//
//        return $this;
//    }
//
//
//    /**
//     * Creates and returns a CLI table for the data in this list
//     *
//     * @param array|string|null $columns
//     * @param array             $filters
//     * @param string|null       $id_column
//     *
//     * @return static
//     */
//    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'tag'): static
//    {
//        $list = [];
//
//        foreach ($this->getSource() as $tag => $selected) {
//            $list[$tag] = ['selected' => $selected ? '*' : ''];
//        }
//
//        $filters = array_replace(
//            $filters,
//            [
//                'tag'   => tr('Tag'),
//                'selected' => tr('Selected'),
//            ]
//        );
//
//        Cli::displayTable(
//            $list,
//            $filters,
//            $id_column
//        );
//
//        return $this;
//    }
}

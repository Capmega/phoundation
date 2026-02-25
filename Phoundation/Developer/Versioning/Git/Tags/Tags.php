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

use Phoundation\Cli\Cli;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\IteratorCore;
use Phoundation\Data\Traits\TraitStaticMethodNewWithRepository;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectGit;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectRepository;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;


class Tags extends IteratorCore implements Interfaces\TagsInterface
{
    use TraitDataObjectGit;
    use TraitDataObjectRepository;
    use TraitStaticMethodNewWithRepository;


    /**
     * Tags class constructor
     *
     * @param RepositoryInterface $_repository
     */
    public function __construct(RepositoryInterface $_repository) {
        parent::__construct();

        $this->setRepositoryObject($_repository)
             ->load();
    }


    /**
     * Loads the remotes for the specified git process
     *
     * @return static
     */
    public function load(): static
    {
        $this->source = $this->_repository->getGitObject()->getTags();
        return $this;
    }


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return $this
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'id'): static
    {
        $source = [];

        foreach ($this as $key => $value) {
            $source[$key] = [
                'tag' => $value,
            ];
        }

        Cli::displayTable($source, $columns, $id_column);
        return $this;
    }
}

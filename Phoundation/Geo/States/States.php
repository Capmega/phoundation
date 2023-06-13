<?php

declare(strict_types=1);

namespace Phoundation\Geo\States;

use Phoundation\Data\DataEntry\DataListInterface;
use Phoundation\Geo\Countries\Country;
use Phoundation\Web\Http\Html\Components\Table;

/**
 * States class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataListInterface
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class States extends DataListInterface
{
    /**
     * States class constructor
     *
     * @param Country|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Country $parent = null, ?string $id_column = null)
    {
        $this->entry_class = State::class;
        self::$table       = State::getTable();

        $this->setHtmlQuery('SELECT   `id`, `name`, `status`, `created_on` 
                                   FROM     `geo_states` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct($parent, $id_column);
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @return Table
     */
    public function getHtmlTable(): Table
    {
        $table = parent::getHtmlTable();
        $table->setCheckboxSelectors(true);

        return $table;
    }


    /**
     * @inheritDoc
     */
    protected function load(string|int|null $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }


    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }
}
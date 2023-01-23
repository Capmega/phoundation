<?php

namespace Phoundation\Geo\Cities;

use Phoundation\Data\DataList\DataList;
use Phoundation\Geo\States\State;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * Cities class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class Cities extends DataList
{
    /**
     * Cities class constructor
     *
     * @param State|null $parent
     * @param string|null $id_column
     */
    public function __construct(State|null $parent = null, ?string $id_column = null)
    {
        $this->entry_class = City::class;
        $this->setHtmlQuery('SELECT `id`, `name`, `status`, `created_on` FROM `geo_cities` WHERE `status` IS NULL ORDER BY `name`');
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
    protected function load(?string $id_column = null): static
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
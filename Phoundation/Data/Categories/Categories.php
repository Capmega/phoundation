<?php

declare(strict_types=1);

namespace Phoundation\Data\Categories;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;

/**
 * Class Categories
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Categories extends DataList
{
    /**
     * Categories class constructor
     *
     * @param Category|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Category $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Category::class;
        $this->table_name  = 'categories';

        $this->setHtmlQuery('SELECT   `id`, `name`, `status`, `created_on` 
                                   FROM     `categories` 
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
     * Returns an HTML <select> object with all available categories
     *
     * @param string $name
     * @return Select
     */
    public static function getHtmlSelect(string $name = 'categories_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT `id`, `name` 
                                          FROM  `categories` 
                                          WHERE `status` IS NULL ORDER BY `name`')
            ->setName($name)
            ->setNone(tr('Please select a category'))
            ->setEmpty(tr('No categories available'));
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
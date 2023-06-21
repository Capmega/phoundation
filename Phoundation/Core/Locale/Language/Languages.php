<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language;

use PDOStatement;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\Select;


/**
 * Languages class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Languages extends DataList
{
    /**
     * Languages class constructor
     */
    public function __construct()
    {
        $this->entry_class = Language::class;
        $this->table       = 'core_languages';

        $this->setQuery('SELECT   `id`, `code_639_1`, `name`, `status`, `created_on` 
                             FROM     `core_languages` 
                             WHERE    `status` IS NULL 
                             ORDER BY `name`');
        parent::__construct();
        $this->load($id_column);
    }




    /**
     * Returns an HTML select component object containing the entries in this list
     *
     * @return SelectInterface
     */
    public function getHtmlSelect(): SelectInterface
    {
        return Select::new()
            ->setSourceQuery('SELECT `id`, `name` FROM `core_languages` WHERE `status` IS NULL ORDER BY `name`')
            ->setName('languages_id')
            ->setNone(tr('Please select a language'))
            ->setEmpty(tr('No languages available'));
    }


    /**
     * @param string|int|null $id_column
     * @return $this
     * @throws \Throwable
     */
    public function load(?string $id_column = null): static
    {
        $this->source = sql()->list('SELECT `core_languages`.`id`, substring_index(substring_index(`core_languages`.`name`, "(", 1), ",", 1) AS `name` 
                                   FROM     `core_languages` 
                                   WHERE    `core_languages`.`status` IS NULL
                                   ORDER BY `name`');

        // The keys contain the ids...
        $this->source = array_flip($this->source);
        return $this;
    }

    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // TODO: Implement loadDetails() method.
    }

    public function save(): static
    {
        // TODO: Implement save() method.
    }
}
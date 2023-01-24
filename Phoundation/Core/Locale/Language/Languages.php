<?php

namespace Phoundation\Core\Locale\Language;

use Phoundation\Data\DataList\DataList;
use Phoundation\Filesystem\File;
use Phoundation\Web\Http\Html\Components\Input\Select;



/**
 * Languages class
 *
 *
 *
 * @see \Phoundation\Data\DataList\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Languages extends DataList
{
    /**
     * Languages class constructor
     *
     * @param Language|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Language $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Language::class;
        $this->table_name  = 'languages';

        $this->setHtmlQuery('SELECT   `id`, `code_639_1`, `name`, `status`, `created_on` 
                             FROM     `languages` 
                             WHERE    `status` IS NULL 
                             ORDER BY `name`');
        parent::__construct($parent, $id_column);
    }



    /**
     * Returns an HTML <select> object with all available languages
     *
     * @param string $name
     * @return Select
     */
    public static function getHtmlSelect(string $name = 'languages_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT `id`, `name` FROM `languages` WHERE `status` IS NULL ORDER BY `name`')
            ->setName($name)
            ->setNone(tr('Please select a language'))
            ->setEmpty(tr('No languages available'));
    }



    /**
     * @param string|null $id_column
     * @return $this
     */
    protected function load(?string $id_column = null): static
    {
        // TODO: Implement load() method.
    }

    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }

    public function save(): static
    {
        // TODO: Implement save() method.
    }
}
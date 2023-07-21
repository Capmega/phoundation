<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language;

use PDOStatement;
use Phoundation\Business\Providers\Provider;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;


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
        $this->setQuery('SELECT   `id`, `code_639_1`, `name`, `status`, `created_on` 
                               FROM     `core_languages` 
                               WHERE    `status` IS NULL 
                               ORDER BY `name`');
        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'core_languages';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Language::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'code_639_1';
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id', ?string $order = null): SelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order)
            ->setName('languages_id')
            ->setNone(tr('Select a language'))
            ->setEmpty(tr('No languages available'));
    }


    /**
     * @param string|null $id_column
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
}
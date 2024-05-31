<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language;

use Phoundation\Core\Locale\Language\Interfaces\LanguagesInterface;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;

/**
 * Languages class
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataList
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */
class Languages extends DataList implements LanguagesInterface
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
    public static function getTable(): ?string
    {
        return 'core_languages';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string|null
     */
    public static function getEntryClass(): ?string
    {
        return Language::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'code_639_1';
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string      $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null  $joins
     * @param array|null  $filters
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order, $joins, $filters)
                     ->setName('languages_id')
                     ->setNotSelectedLabel(tr('Select a language'))
                     ->setComponentEmptyLabel(tr('No languages available'));
    }


    /**
     * Load the id list from the database
     *
     * @param bool $clear
     *
     * @return static
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static
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

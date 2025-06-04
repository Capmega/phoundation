<?php

/**
 * Languages class
 *
 *
 *
 * @see       DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Locale\Language;

use Phoundation\Accounts\Users\Locale\Language\Interfaces\LanguagesInterface;
use Phoundation\Data\DataEntries\DataIterator;


class Languages extends DataIterator implements LanguagesInterface
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
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'core_languages';
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
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
     * Load the id list from the database
     *
     * @param array|string|int|null $identifiers
     * @param bool $only_if_empty
     *
     * @return static
     */
    public function load(array|string|int|null $identifiers = null, bool $only_if_empty = false): static
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

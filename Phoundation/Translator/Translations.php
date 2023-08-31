<?php

declare(strict_types=1);

namespace Phoundation\Translator;

use Phoundation\Core\Config;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;
use Phoundation\Web\Routing\StaticRoute;


/**
 * Class Translations
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Translator
 */
class Translations extends DataList
{
    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'translations';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Translation::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return null;
    }


    /**
     * Returns what languages are configured for translations
     *
     * @return array
     */
    public function getLanguages(): array
    {
        return Config::getArray('translations.languages', ['en']);
    }


    /**
     * Deletes all translation copies of this project
     *
     * @return static
     */
    public function clean(): static
    {
throw new UnderConstructionException();
        return $this;
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'translation', string $key_column = 'id', ?string $order = null): InputSelectInterface
    {
        return InputSelect::new()
            ->setSourceQuery('SELECT   `' . $key_column . '`, `' . $value_column . '` 
                                         FROM     `' . static::getTable() . '` 
                                         WHERE    `status` IS NULL 
                                         ORDER BY `' . $value_column . '` ASC')
            ->setName('translations_id')
            ->setNone(tr('Select a translation'))
            ->setEmpty(tr('No translations available'));
    }
}

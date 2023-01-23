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
     * Import the content for the languages table from a data-source file
     *
     * @return void
     */
    public function import(): void
    {
        $file = File::new(PATH_DATA . 'sources/languages/languages');
        $h    = $file->open('r');

        $this->getTable()->truncate();

        while($line = fgets($h, $file->getBufferSize())) {
            // Parse the line
            switch ($line[0]) {
                case '#':
                    // no break
                case ';':
                    // no break
                case '//':
                    continue 2;
            }

            $line = explode("\t", $line);

            // Import the language data into a language object and save.
            $language = Language::new();
            $language->setName(isset_get($line[0]));
            $language->setCode_639_1(isset_get($line[1]));
            $language->setCode_639_2_t(isset_get($line[2]));
            $language->setCode_639_2_b(isset_get($line[3]));
            $language->setCode_639_3(isset_get($line[4]));
            $language->setDescription(isset_get($line[5]));
            $language->save();
        }
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
            ->setSourceQuery('SELECT `code_639_1`, `name` FROM `languages` WHERE `status` IS NULL ORDER BY `name`')
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
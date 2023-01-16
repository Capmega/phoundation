<?php

namespace Phoundation\Core\Locale\Language;

use Phoundation\Data\DataList;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Web\Http\Html\Components\Input\Select;



/**
 * Languages class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Languages extends DataList
{
    /**
     * Import the content for the languages table from a data-source file
     *
     * @return void
     */
    public static function import(): void
    {
        $file = File::new(PATH_DATA . 'sources/languages/languages');
        $h    = $file->open('r');

        sql()->truncate('languages');

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
     * @return Select
     */
    public static function getHtmlSelect(): Select
    {
        $select = Select::new();
        $select->setSourceQuery('SELECT `code_639_1`, `name` FROM `languages` WHERE `status` IS NULL ORDER BY `name`');

        return $select;
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
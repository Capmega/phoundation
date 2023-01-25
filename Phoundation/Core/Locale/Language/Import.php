<?php

namespace Phoundation\Core\Locale\Language;

use Phoundation\Core\Log;
use Phoundation\Filesystem\File;


/**
 * Import class
 *
 * This class can import language data from the ROOT/data/sources/languages path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Core
 */
class Import extends \Phoundation\Developer\Project\Import
{
    /**
     * Import constructor
     */
    protected function __construct()
    {
        self::$table = 'languages';
    }



    /**
     * Import the content for the languages table from a data-source file
     *
     * @return void
     */
    public static function execute(): void
    {
        self::getInstance();

        Log::information(tr('Starting languages import'));
        parent::execute();

        $file  = File::new(PATH_DATA . 'sources/languages/languages');
        $h     = $file->open('r');
        $count = self::getTable()->getCount();

        if ($count and !FORCE) {
            Log::warning(tr('Not importing data for ":table", the table already contains data', [
                ':table' => self::$table
            ]));

            return;
        }

        self::getTable()->truncate();

        Log::action(tr('Importing languages, this may take a few seconds...'));
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



}
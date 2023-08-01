<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\File;


/**
 * Import class
 *
 * This class can import language data from the ROOT/data/sources/languages path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Core
 */
class Import extends \Phoundation\Developer\Project\Import
{
    /**
     * Import class constructor
     *
     * @param bool $demo
     * @param int|null $min
     * @param int|null $max
     */
    public function __construct(bool $demo = false, ?int $min = null, ?int $max = null)
    {
        parent::__construct($demo, $min, $max);
        $this->name = 'Languages';
    }


    /**
     * Import the content for the languages table from a data-source file
     *
     * @return int
     */
    public function execute(): int
    {
        Log::information(tr('Starting languages import'));

        if ($this->demo) {
            Log::notice('Ignoring "demo" mode for Languages, this does not do anything for this library');
        }

        $file  = File::new(PATH_DATA . 'sources/languages/languages');
        $h     = $file->open('r');
        $table = sql()->schema()->table('core_languages');
        $count = $table->getCount();

        if ($count and !FORCE) {
            Log::warning(tr('Not importing data for "languages", the table already contains data'));
            return 0;
        }

        sql()->query('DELETE FROM `core_languages`');

        $count  = 0;
        $buffer = $file->getBufferSize();

        Log::action(tr('Importing languages, this may take a few seconds...'));

        while($line = fgets($h, $buffer)) {
            $count++;

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
            $language->setName(Strings::until(isset_get($line[0]), '('));
            $language->setCode_639_1(isset_get($line[1]));
            $language->setCode_639_2_t(isset_get($line[2]));
            $language->setCode_639_2_b(isset_get($line[3]));
            $language->setCode_639_3(substr(isset_get($line[4]), 0, 3));
            $language->setDescription(isset_get($line[5]));
            $language->save();
        }

        return $count;
    }
}
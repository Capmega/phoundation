<?php

/**
 * Import class
 *
 * This class can import language data from the ROOT/data/sources/languages path
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Locale\Language;

use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\Enums\EnumFileOpenMode;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Strings;


class Import extends \Phoundation\Developer\Project\Import
{
    /**
     * Import class constructor
     *
     * @param bool     $demo
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
        Log::information(ts('Starting languages import'));

        if ($this->demo) {
            Log::notice('Ignoring "demo" mode for Languages, this does not do anything for this library');
        }

        $file  = PhoFile::new(
            DIRECTORY_DATA . 'sources/languages/languages',
            PhoRestrictions::newReadonlyObject(DIRECTORY_DATA)
        )->open(EnumFileOpenMode::readOnly);

        $table = sql()->getSchemaObject()->getTableObject('core_languages');
        $count = $table->getCount();

        if ($count and !FORCE) {
            Log::warning(ts('Not importing data for "languages", the table already contains data'));

            return 0;
        }

        sql()->query('DELETE FROM `core_languages`');

        $count  = 0;
        $buffer = $file->getBufferSize();

        Log::action(ts('Importing languages, this may take a few seconds...'));

        while ($line = $file->readLine($buffer)) {
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
            Language::new()->setName(Strings::until(isset_get($line[0]), '('))
                           ->setCode6391(isset_get($line[1]))
                           ->setCode6392T(isset_get($line[2]))
                           ->setCode6392B(isset_get($line[3]))
                           ->setCode6393(substr(isset_get($line[4]), 0, 3))
                           ->setDescription(isset_get($line[5]))
                           ->save();
        }

        $file->close();

        return $count;
    }
}

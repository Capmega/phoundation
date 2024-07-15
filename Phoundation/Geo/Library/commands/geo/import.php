<?php

/**
 * Command geo ip import
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Geo\Import;

CliDocumentation::setUsage('./pho geo import');

CliDocumentation::setHelp('This command will download and import the geonames data files

The entire import consists of the following steps (which may be skipped using the specified arguments)

* Download the geonames datafiles (~400MB in download, ~2GB in storage in ROOT/data/sources/geo/geonames, takes about
  1-5 minutes)
* Load the geonames datafiles into the separate geonames database (Adds ~2GB to your mysql databases storage, takes
  about 3-15 minutes)
* Cleanup the geonames database and import them into your projects database (Adds ~2GB to your mysql databases storage,
  takes about 5-60 minutes)



ARGUMENTS



[-d / --database DATABASE]              If defined, will not use the geonames database but the specified database
                                        instead

[-i / --import]                         Start the entire import from the geonames loaded database (so do not download
                                        any data files and do not load any datafiles into a geonames database)

[-l / --load]                           Start the entire import from the source files path (defaults to
                                        ROOT/data/sources/geo/geonames/) so do not download anything

[-s / --source-path PATH]               If specified, this script will not download the files but use the files
                                        available in the specified PATH instead

[-t / --target-path PATH]               If specified, this script will move the Geo IP files to the specified target
                                        path instead of the default of ROOT/data/sources/geo/geonames/');


$argv = ArgvValidator::new()
                     ->select('-t,--target_path', true)->isOptional(DIRECTORY_DATA . 'sources/geo')->isDirectory(FsDirectory::getData())
                     ->select('-l,--no-download')->isOptional(false)->isBoolean()
                     ->select('-i,--no-import')->isOptional(false)->isBoolean()
                     ->select('--ignore-sha-fail')->isOptional(false)->isBoolean()
                     ->validate();


// Download
if (!$argv['no_download'] and !$argv['no_import']) {
    Log::information(tr('Downloading geonames data'));

    // Download the files
    Import::download($argv['target_path']);
}


// Verify integrity and import into temporary database
if (!$argv['no_import']) {
    Log::information(tr('Importing geonames data'));

    // Process the files
    Log::action(tr('Processing geonames files'));
    Import::process($argv['target_path'], $argv['target_path'] . '_processed', FsRestrictions::new(DIRECTORY_DATA, true, 'import'));

    // Load the datafiles into a geonames database
    Log::action(tr('Loading geonames files into temporary database'));
    Import::load($argv['target_path'] . '_processed', $argv['database']);
}


// Transfer temporary database data to Geo library tables
Log::action(tr('Importing geonames data into core database'));
Import::import($argv['database']);

Log::success(tr('Finished importing all Geo data'));

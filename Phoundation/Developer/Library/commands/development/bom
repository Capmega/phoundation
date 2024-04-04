<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Tests\BomFile;
use Phoundation\Developer\Tests\Exception\BomException;
use Phoundation\Filesystem\Restrictions;


/**
 * Script bom
 *
 * This script can check for - and remove Unicode Byte Order Marks from files
 * ./pho dev bom
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setHelp('The bom script can search for and clear the BOM (Byte Order Mark) from all PHP files found in the specified path.

The bom script uses the cache file ROOT/data/system/mtime to store the minimum file mtime for files to be checked.
If files have an mtime older than the mtime of that cache file, they will not be scanned to speed up the BOM check.
Use -n or --nomtime to skip this check. Use -c or --cachemtime to set the mtime of the cache file


ARGUMENTS


-t --test                              Do not clear the BOM from files when found, report only

-n --no-mtime                          Do not perform file minimum mtime check to speed up BOM scan

-m --m-time                            Use the specified mtime, instead of the cache file mtime

-c --cache-mtime                       Set the mtime of the cache for subsequent scans');

CliDocumentation::setUsage('./pho dev bom PATH
./pho dev bom --nomtime PATH
./pho dev bom --cachemtime DATE PATH
');


// Get arguments
$argv = ArgvValidator::new()
                     ->select('--test')->isOptional(false)->isBoolean()
                     ->select('--no-mtime')->isOptional(false)->isBoolean()
                     ->select('--cache-mtime', true)->isOptional()->isDateTime()
                     ->select('file')->isFile(DIRECTORY_ROOT, Restrictions::writable(DIRECTORY_ROOT))
                     ->validate();


if ($argv['test']) {
    if (BomFile::new($argv['file'])->hasBom()) {
        throw new BomException(tr('A BOM was found in the file ":file"', [
            ':file' => $argv['file'],
        ]));

    } else {
        Log::success(tr('The file ":file" has no BOM', [
            ':file' => $argv['file'],
        ]));
    }

} else {
    if (BomFile::new($argv['file'])->clearBom()) {
        Log::success(tr('A BOM was found and removed from the file ":file"', [
            ':file' => $argv['file'],
        ]));

    } else {
        Log::success(tr('The file ":file" has no BOM', [
            ':file' => $argv['file'],
        ]));
    }
}

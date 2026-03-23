<?php

/**
 * Command web move
 *
 * Moves a web page from the source location to the destination location and leaves a redirect file in place so that users won't be lost on a 404 page
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Web\Requests\Redirects\Redirect;

CliDocumentation::setAutoComplete([
    'positions' => [
    ],
    'arguments' => [
    ],
]);

CliDocumentation::setUsage('./pho web move 
./pho web server install -e production -s nginx');

CliDocumentation::setHelp('This command helps with the installation of the webserver\'s virtualhost file

The virtualhost file will be installed by placing a symlink in the servers "sites-available" directory.

Currently only supported on Debian Linux style servers 


ARGUMENTS


SOURCE FILE                             The source file that needs to move to the target location. May be an absolute filename, or a relative filename starting 
                                        from web/ 

TARGET FILE                             The target file where the source needs to be moved to. May be an absolute filename, or a relative filename starting 
                                        from web/

REDIRECT URL                            The redirect URL for users that will HTTP 301 redirect their -now no longer existing- source page to the new target');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('source', true)->sanitizeFile(PhoDirectory::newRoot())
                     ->select('target', true)->sanitizeFile(PhoDirectory::newRoot())
                     ->select('redirect', true)->sanitizeMakeUrlObject()
                     ->validate();


// Implement the redirect
Redirect::new()
        ->setSourceFileObject($argv['source'])
        ->setTargetFileObject($argv['target'])
        ->setRedirectUrlObject($argv['redirect'])
        ->save();

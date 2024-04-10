<?php

declare(strict_types=1);

/**
 * Script try
 *
 * General quick try and test script. Scribble any test code that you want to execute here and execute it with
 * ./pho test
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;

$root        = Path::new(DIRECTORY_ROOT, Restrictions::writable(DIRECTORY_DATA, tr('test!')));
$test        = Path::new(DIRECTORY_DATA . 'test', Restrictions::writable(DIRECTORY_DATA, tr('test!')));
$data        = Path::new(DIRECTORY_DATA, Restrictions::writable(DIRECTORY_DATA, tr('test!')));
$target      = Path::new(DIRECTORY_DATA . 'target', Restrictions::writable(DIRECTORY_DATA, tr('test!')));
$phoundation = Path::new('~/Downloads/firefox', Restrictions::readonly('~', tr('test!')));

$yolo = $test->symlinkTreeToTarget($target);

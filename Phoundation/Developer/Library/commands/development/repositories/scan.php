<?php

/**
 * Command development repositories scan
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Core\Log\Log;
use Phoundation\Developer\Phoundation\Repositories\Repositories;
use Phoundation\Developer\Phoundation\Repositories\Repository;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Strings;


$repositories = Repositories::new()->scan();

foreach (Repositories::getTypes() as $label => $type) {
    $list = $repositories->getRepositoryType($type);

    Log::information(tr(':data repositories', [':data' => $label]), 10, echo_prefix: false);

    if ($list->isEmpty()) {
        Log::notice('-', 10, echo_prefix: false);
        Log::cli(' ');

    } else {
        foreach ($list as $name => $repository) {
            Log::write(Strings::size($repository->getName(), 30), 'debug', 10, false, false, false);
            Log::notice($repository->getSource(), 10, echo_prefix: false);

            $start = true;

            if ($repository->isVendorsRepository()) {
                foreach ($repository->getVendors() as $vendor) {
                    if ($start) {
                        Log::information(Strings::size('', 30) . Strings::size(tr('Vendors'), 30), 10, false, false, false);
                        $start = false;

                    } else {
                        Log::notice(Strings::size('', 60), 10, false, false, false);
                    }

                    Log::write(Strings::size($vendor->getName(), 30), 'debug', 10, false, true, false);
                }
            }

            Log::cli(' ');
        }
    }
}

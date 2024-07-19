<?php

/**
 * Class LsBlk
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */

declare(strict_types=1);

namespace Phoundation\Filesystem\Commands;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataPath;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsMountedStorageDevices;
use Phoundation\Os\Processes\Commands\Command;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Utils;

class LsBlk extends Command
{
    /**
     * Executes the lsblk command
     *
     * @return array
     */
    public function executeReturnArray(): array
    {
        // Display all devices, display bytes instead of human readable sizes
        $this->setCommand('lsblk');
        $this->addArguments(['-a', '-b', '-l', '-f', '--json']);

        return parent::executeReturnArray();
    }


    /**
     * Returns the output of the DF command in a usable Iterator interface
     *
     * @todo This method should auto execute if not executed yet, or fail gracefully
     *
     * @return IteratorInterface
     */
    public function getResults(): IteratorInterface
    {
        $return  = [];
        $results = Json::decode($this->getStringOutput());
        $results = $results['blockdevices'];
        $devices = FsMountedStorageDevices::new()->scan();

        // Fix the device names and update the keys
        foreach ($results as $result) {
            if (str_starts_with($result['name'], 'loop')) {
                $result['device'] = '/dev/' . $result['name'];

            } else {
                $match = $devices->getMatchingValues($result['name'], Utils::MATCH_ENDS_WITH)->getFirstValue();

                if ($match) {
                    // This is a "linked" device
                    $result['device'] = FsFile::new($match)->getLinkTarget()->getSource();
                    $result['device'] = FsFile::normalizePath($result['device']);

                } else {
                    // This is a hard device
                    $result['device'] = '/dev/' . $result['name'];
                }
            }

            $return[$result['device']] = $result;
        }

        return new Iterator($return);
    }
}

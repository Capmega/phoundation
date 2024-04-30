<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Commands;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataPath;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\MountedStorageDevices;
use Phoundation\Os\Processes\Commands\Command;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Utils;

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
class LsBlk extends Command
{
    /**
     * Executes the lsblk command
     *
     * @return array
     */
    public function executeReturnArray(): array
    {
        $this->setCommand('lsblk');
        $this->addArguments(['-a', '-b', '-l']); // Display all devices, display bytes instead of human readable sizes

        return parent::executeReturnArray();
    }


    /**
     * Returns the output of the DF command in a usable Iterator interface
     *
     * @param bool $devices_too
     *
     * @return IteratorInterface
     */
    public function getResults(bool $devices_too = false): IteratorInterface
    {
        $return  = [];
        $results = Arrays::fromCsvSource($this->output, [
            'filesystem' => ' ',
            'type'       => ' ',
            'size'       => ' ',
            'used'       => ' ',
            'available'  => ' ',
            'use'        => ' ',
            'mountedon'  => ' ',
        ], 'filesystem');

        $devices = MountedStorageDevices::new()->scan();

        // Fix the device names and update the keys
        foreach ($results as $entry) {
            $return[$entry['filesystem']] = $entry;

            if (str_starts_with($entry['filesystem'], 'loop')) {
                $entry['device'] = '/dev/' . $entry['filesystem'];

            } else {
                $match = $devices->getMatchingValues($entry['filesystem'], Utils::MATCH_ENDS_WITH)->getFirstValue();

                if ($match) {
                    $entry['device'] = '/dev/disk/by-id/' . File::new('/dev/disk/by-id/' . $match)->getLinkTarget()->getPath();
                    $entry['device'] = File::normalizePath($entry['device']);

                } else {
                    $entry['device'] = '/dev/' . $entry['filesystem'];
                }
            }

            $return[$entry['filesystem']] = $entry;
        }

        if ($devices_too) {
            // Add the devices too as those are the real block devices
            foreach ($return as $value) {
                $return[$value['device']] = $value;
            }
        }

        return new Iterator($return);
    }
}

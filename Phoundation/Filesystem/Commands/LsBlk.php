<?php

/**
 * Class LsBlk
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Commands;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataObjectPath;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoMountedStorageDevices;
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
        // Display all devices, display bytes instead of human-readable sizes
        $this->setCommand('lsblk');
        $this->addArguments(['-a', '-b', '-l', '-f', '--json']);

        return parent::executeReturnArray();
    }


    /**
     * Returns the output of the lsblk command in a usable Iterator interface
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
        $devices = PhoMountedStorageDevices::new()->scan();
//show($results);
        // Fix the device names and update the keys
        foreach ($results as $result) {
            if (str_starts_with($result['name'], 'loop')) {
                $result['device'] = '/dev/' . $result['name'];

            } else {
show($result['name']);
                $match = $devices->getMatchingValues($result['name'], Utils::MATCH_ENDS_WITH, 'source')->getFirstValue();

                if ($match) {
                    // This is a "linked" device
                    $result['device'] = PhoFile::new($match)->getLinkTarget()->getSource();
                    $result['device'] = PhoFile::absolutePath($result['device']);

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

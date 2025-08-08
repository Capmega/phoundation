<?php

/**
 * Class BtrfsFilesystem
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Filesystems\Btrfs;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Filesystems\Btrfs\Exception\FilesystemBtrfsException;
use Phoundation\Filesystem\Filesystems\Btrfs\Interfaces\BtrfsDeviceInterface;
use Phoundation\Filesystem\Filesystems\Btrfs\Interfaces\BtrfsFilesystemInterface;
use Phoundation\Filesystem\Filesystems\Btrfs\Interfaces\BtrfsSubVolumeInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Utils\Strings;


class BtrfsFilesystem extends Btrfs implements BtrfsFilesystemInterface
{
    /**
     * Returns new static object
     *
     * @param PhoPathInterface|null $o_path
     *
     * @return static
     */
    public static function new(?PhoPathInterface $o_path = null): static
    {
        return new static ($o_path);
    }


    /**
     * Returns a key/value Iterator containing usage information for this filesystem
     *
     * @return IteratorInterface
     */
    public function getUsage(): IteratorInterface
    {
        $iterator = [];
        $return   = $this->o_process->addArguments(['filesystem', 'usage', $this->o_path->getSource(), '-T', '-b'])
                                    ->executeReturnIterator();

        foreach ($return as $usage) {
            $usage = trim($usage);

            if (empty($usage)) {
                break;
            }

            $usage = strtolower($usage);
            $value = Strings::from($usage, ':', needle_required: true);
            $value = trim($value);

            if ($value === '') {
                continue;
            }

            if (str_contains($value, '(')) {
                // This line contains extra information. Parse and remove that information
                $sub_key   = Strings::cut($value, '(', ':');
                $sub_key   = trim($sub_key);
                $sub_value = Strings::cut($value, ':', ')');
                $sub_value = trim($sub_value);
                $value     = Strings::until($value, ')');
                $key       = $this->getCleanKey($usage);

                $iterator[$key . '_' . $sub_key] = $sub_value;

                // Remove the extra information
                $value = Strings::until($value, '(');
                $value = trim($value);

            } else {
                $key = $this->getCleanKey($usage);
            }

            // Add the key/value pair
            $iterator[$key] = $value;
        }

        return new Iterator($iterator);
    }


    /**
     * Returns a key/value Iterator containing usage information per device for this filesystem
     *
     * @return IteratorInterface
     */
    public function getDeviceUsage(): IteratorInterface
    {
        $pass     = false;
        $iterator = [];
        $return   = $this->o_process->addArguments(['filesystem', 'usage', $this->o_path->getSource(), '-T', '-b'])
                                    ->executeReturnIterator();

        foreach ($return as $usage) {
            $usage = trim($usage);

            if (str_starts_with($usage, '--')) {
                if ($pass) {
                    // This is the closing line, break!
                    break;
                }

                $pass = true;
                continue;
            }

            if (!$pass) {
                continue;
            }

            $usage = Strings::from($usage, ' ');
            $usage = Strings::replaceDouble($usage, characters: ' ');
            $usage = explode(' ', $usage);
            $usage = array_combine(['path', 'data_single', 'metadata_dup', 'system_dup', 'unallocated', 'total', 'slack'], $usage);

            // Add the key/value pair
            $iterator[$usage['path']] = $usage;
        }

        return new Iterator($iterator);
    }


    /**
     * Returns a clean key for the specified key
     *
     * @param string $key
     *
     * @return string
     */
    protected function getCleanKey(string $key): string
    {
        $key = Strings::until($key, ':');
        $key = str_replace(['(', ')', ','], '', $key);
        $key = str_replace(' ', '_', $key);
        $key = trim($key);

        return $key;
    }


    /**
     * Returns basic information on all BTRFS filesystems mounted, or all devices with a BTRFS filesystems under /dev
     *
     * @param bool $mounted [true] If true will only display mounted BTRFS filesystems. If false, will display all BTRFS filesystems under /dev
     *
     * @return IteratorInterface
     */
    public function getFilesystems(bool $mounted = true): IteratorInterface
    {
        $return = [];
        $result = $this->o_process->addArguments(['filesystem', 'show', $mounted ? '--mounted' : '--all-devices'])
                                  ->executeReturnString();

        foreach (explode("\n\n", $result) as $filesystem) {
            $filesystem = $this->parseFilesystemData($filesystem);
            $return[$filesystem['path']] = $filesystem;
        }

        return new Iterator($return);
    }


    /**
     * Parses output from a single section from output of "btrfs filesystem show"
     *
     * @param string $data
     *
     * @return array
     */
    protected function parseFilesystemData(string $data): array
    {
        $data = Strings::ensureEndsNotWith($data, "\n");

        // Do some sanity checks
        if (substr_count($data, "\n") !== 2) {
            throw FilesystemBtrfsException::new(tr('Cannot parse filesystem data, it should contain 3 \n characters but has ":count" instead', [
                ':count' => substr_count($data, "\n"),
            ]))->addData([
                'data' => $data,
            ]);
        }

        $return = [];
        $data   = explode("\n", $data);

        $return['label']         = Strings::cut($data[0], ': ', ' ');
        $return['label']         = trim($return['label']);
        $return['uuid']          = Strings::fromReverse($data[0], ':');
        $return['uuid']          = trim($return['uuid']);
        $return['total_devices'] = Strings::cut($data[1], 'Total devices', 'FS');
        $return['total_devices'] = trim($return['total_devices']);
        $return['used']          = Strings::fromReverse($data[1], ' ');
        $return['used']          = trim($return['used']);
        $return['dev_id']        = Strings::from($data[2], 'devid');
        $return['dev_id']        = trim($return['dev_id']);
        $return['dev_id']        = Strings::until($return['dev_id'], ' ');
        $return['dev_id']        = trim($return['dev_id']);
        $return['size']          = Strings::from($data[2], 'size');
        $return['size']          = trim($return['size']);
        $return['size']          = Strings::until($return['size'], ' ');
        $return['path']          = Strings::fromReverse($data[2], ' ');

        return $return;
    }


    /**
     * Scan or forget (unregister) devices of btrfs filesystems
     *
     * Executes "btrfs device add DIRECTORY"
     *
     * @param BtrfsDeviceInterface $device
     *
     * @return static
     */
    public function add(BtrfsDeviceInterface $device): static
    {
throw new UnderConstructionException();
        $o_return = $this->o_process->addArguments(['device', 'add'])
                                    ->executeReturnIterator()
                                    ->removeKeys(0);

        foreach ($o_return as $key => $device) {
            $device = Strings::from($device, ':');
            $device = trim($device);

            $o_return->set($device, $key);
        }

        return $o_return;
    }


    /**
     * Returns the subvolume for the specified path
     *
     * @note If specified $path is absolute, it MUST be under this objects' filesystem path. If path is relative, it will be made absolute using the current
     *       objects' filesystem path as a prefix
     *
     * @param string $path
     *
     * @return BtrfsSubVolumeInterface
     */
    public function getSubvolume(string $path): BtrfsSubVolumeInterface
    {
        return new BtrfsSubvolume($this, $path);
    }
}

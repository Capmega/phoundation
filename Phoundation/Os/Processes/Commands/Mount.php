<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Exception\FilesystemDriverMissingException;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Mounts\Exception\NotMountedException;
use Phoundation\Filesystem\Mounts\Mounts;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Strings;
use Stringable;


/**
 * Class Mount
 *
 *
 * @note On Ubuntu requires packages nfs-utils cifs-utils psmisc
 * * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Mount extends Command
{
    /**
     * Mount class constructor
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null $operating_system
     * @param string|null $packages
     */
    public function __construct(RestrictionsInterface|array|string|null $restrictions = null, ?string $operating_system = null, ?string $packages = null)
    {
        parent::__construct($restrictions, $operating_system, $packages);
        $this->packages->addForOperatingSystem('debian', 'nfs-utils,cifs-utils,psmisc');
    }


    /**
     * Mount the specified source to the specified target
     *
     * @param Stringable|string $source
     * @param Stringable|string $target
     * @param string|null $filesystem
     * @param Stringable|array|string|null $options
     * @return void
     */
    public function mount(Stringable|string $source, Stringable|string $target, ?string $filesystem = null, Stringable|array|string|null $options = null): void
    {
        Directory::new($target, $this->restrictions)->ensure();

        try {
            // Build the process parameters, then execute
            $this->clearArguments()
                ->setSudo(true)
                ->setCommand('mount')
                ->addArguments([(string)$source, (string)$target])
                ->addArguments($options ? ['-o', Strings::force($options, ',')] : null)
                ->addArguments($filesystem ? ['-t', $filesystem] : null)
                ->executeNoReturn();

        } catch (ProcessFailedException $e) {
            // Failed to mount, try to figure out what went wrong to give a clear error message
            if ($e->dataContains('you might need a /sbin/mount.<type> helper program.', key: 'output') and in_array($filesystem, ['nfs'])) {
                // This is a missing filesystem driver
                throw FilesystemDriverMissingException::new(tr('Failed to mount ":source" to ":target", a driver is missing for filesystem ":fs". On Debian type systems, try "sudo apt install nfs-common"', [
                    ':source' => $source,
                    ':target' => $target,
                    ':fs'     => $filesystem
                ]), $e)->makeWarning();
            }

            throw $e;
        }
    }


    /**
     * Returns a list of all current mount points
     *
     * @return IteratorInterface
     */
    public function list(): IteratorInterface
    {
        // Build the process parameters, then execute
        return $this->clearArguments()
            ->setCommand('mount')
            ->executeReturnIterator();
    }


    /**
     * Returns a list of all directories where the specified device is mounted
     *
     * @param FileInterface|string $device
     * @return IteratorInterface
     */
    public function deviceMountList(FileInterface|string $device): IteratorInterface
    {
        return $this->clearArguments()
            ->setCommand('mount')
            ->setPipe(Process::new('grep')->addArgument($device))
            ->executeReturnIterator();
    }


    /**
     * Returns the number of times this device is mounted
     *
     * @param FileInterface|string $device
     * @return int
     */
    public function deviceMountCount(FileInterface|string $device): int
    {
        return $this->deviceMountList($device)->getCount();
    }


    /**
     * Returns a list of all current mount points
     *
     * @param FileInterface|string $device
     * @return bool
     */
    public function deviceIsMounted(FileInterface|string $device): bool
    {
        return (bool) $this->deviceMountCount($device);
    }


    /**
     * Returns true if the specified path is a mount source, false if it is a target.
     *
     * This method will throw an NotAMountException if the specified path is not mounted
     *
     * @param Stringable|string $path
     * @param bool $exception
     * @return bool|null
     */
    public static function isSource(Stringable|string $path, bool $exception = true): ?bool
    {
        $path      = Directory::new($path, Restrictions::new('/'))->getPath(true);
        $sources   = Mounts::listMountSources();
        $targets   = Mounts::listMountTargets();
        $is_source = $sources->keyExists($path);
        $is_target = $targets->keyExists($path);

        if ($is_source) {
            if ($is_target) {
                // Wut? its target that was used as a source? Could be a bind? Should this be logged?
                return true;
            }

            return true;

        } else {
            // Not a source
            if ($is_target) {
                return false;
            }

            // Not even mounted!
            if ($exception) {
                throw new NotMountedException(tr('Cannot check path ":path" to see if it is a mount source, is neither a mount source or target', [
                    ':path' => $path
                ]));
            }

            return null;
        }
    }


    /**
     * Returns true if the specified path is a mount source, false if it is a target.
     *
     * This method will throw an NotAMountException if the specified path is not mounted
     *
     * @param Stringable|string $path
     * @param bool $exception
     * @return bool|null
     */
    public static function isTarget(Stringable|string $path, bool $exception = true): ?bool
    {
        return null_not(static::isSource($path, $exception));
    }


    /**
     * Returns true if the specified path is a mount source, false if it is a target.
     *
     * @param Stringable|string $path
     * @return bool
     */
    public static function isMounted(Stringable|string $path): bool
    {
        $path = Directory::new($path, Restrictions::new('/'))->getPath(true);
        return Mounts::listMountTargets()->keyExists($path);
    }
}

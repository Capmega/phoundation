<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Arrays;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Os\Processes\Process;
use Stringable;


/**
 * Class Mount
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Mount extends Command
{
    /**
     * Mount the specified source to the specified target
     *
     * @param Stringable|string $source
     * @param Stringable|string $target
     * @param array|null $options
     * @param string|null $filesystem
     * @return void
     */
    public function mount(Stringable|string $source, Stringable|string $target, ?array $options = null, ?string $filesystem = null): void
    {
        // Build the process parameters, then execute
        $this->clearArguments()
            ->setSudo(true)
            ->setInternalCommand('mount')
            ->addArguments([(string) $source, (string) $target])
            ->addArguments($options ? ['-o', $options] : null)
            ->addArguments($filesystem ? ['-t', $filesystem] : null)
            ->executeNoReturn();
    }


    /**
     * Unmount the specified source to the specified target
     *
     * @param Stringable|string $target
     * @param array|string|null $options
     * @return void
     */
    public function unmount(Stringable|string $target, array|string|null $options = null): void
    {
        // Build the process parameters, then execute
        $this->clearArguments()
            ->setSudo(true)
            ->setInternalCommand('umount')
            ->addArguments((string) $target)
            ->addArguments(Arrays::force($options))
            ->executeNoReturn();
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
            ->setInternalCommand('mount')
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
            ->setInternalCommand('mount')
            ->setPipe(Process::new('grep')->addArgument($device))
            ->executeReturnIterator();
    }


    /**
     * Returns the amount of times this device is mounted
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
}

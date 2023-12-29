<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\DataForce;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Mounts\Exception\NotMountedException;
use Phoundation\Filesystem\Mounts\Exception\UnmountBusyException;
use Phoundation\Filesystem\Mounts\Mounts;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Stringable;


/**
 * Class Mount
 *
 *
 * @note On Ubuntu requires packages nfs-utils cifs-utils psmisc
 * * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class UnMount extends Command
{
    use DataForce;


    /**
     * Sets if lazy should be used
     *
     * @var bool $lazy
     */
    protected bool $lazy = false;


    /**
     * Returns if lazy should be used
     *
     * @return bool
     */
    public function getLazy(): bool
    {
        return $this->lazy;
    }


    /**
     * Sets if lazy should be used
     *
     * @param bool $lazy
     * @return static
     */
    public function setLazy(bool $lazy): static
    {
        $this->lazy = $lazy;
        return $this;
    }


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
     * Unmount the specified target
     *
     * @param Stringable|string $target
     * @return void
     */
    public function unmount(Stringable|string $target): void
    {
        if (Mount::isSource($target, false)) {
            // This is a mount source. Unmount all its targets
            $targets = Mounts::listMountTargets($target);

            foreach ($targets as $target) {
                static::unmount($target);
            }

        } else {
            if (!Mount::isMounted($target)) {
                throw new NotMountedException(tr('Cannot unmount target ":target", it is not mounted', [
                    ':target' => $target
                ]));
            }

            // Build the process parameters, then execute
            try {
                $this->clearArguments()
                    ->setSudo(true)
                    ->setCommand('umount')
                    ->addArgument($this->force ? '-f' : null)
                    ->addArgument($this->lazy  ? '-l' : null)
                    ->addArgument($target)
                    ->executeNoReturn();

            } catch (ProcessFailedException $e) {
                if (!$e->dataContains('device is busy', key: 'output')) {
                    throw $e;
                }

                $processes = Lsof::new()->getForFile($target);

                // The device is busy. Check by who and add it to the exception
                throw UnmountBusyException::new(tr('Cannot unmount target ":target", it is busy', [
                    ':target' => $target
                ]))->addData(['processes' => $processes]);
            }
        }
    }
}

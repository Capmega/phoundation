<?php

/**
 * Class FsMount
 *
 *
 * @note      On Ubuntu requires packages nfs-utils cifs-utils psmisc
 * * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Hooks\Hook;
use Phoundation\Data\Traits\TraitDataForce;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\Mounts\Exception\NotMountedException;
use Phoundation\Filesystem\Mounts\Exception\UnmountBusyException;
use Phoundation\Filesystem\Mounts\PhoMounts;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Stringable;


class UnMount extends Command
{
    use TraitDataForce;

    /**
     * Sets if lazy should be used
     *
     * @var bool $lazy
     */
    protected bool $lazy = false;


    /**
     * FsMount class constructor
     *
     * @param PhoRestrictionsInterface|PhoDirectoryInterface|null $execution_directory
     * @param string|null                                         $operating_system
     * @param string|null                                         $packages
     */
    public function __construct(PhoRestrictionsInterface|PhoDirectoryInterface|null $execution_directory = null, ?string $operating_system = null, ?string $packages = null)
    {
        parent::__construct($execution_directory, $operating_system, $packages);
        $this->packages->addForOperatingSystem('debian', 'nfs-utils,cifs-utils,psmisc');
    }


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
     *
     * @return static
     */
    public function setLazy(bool $lazy): static
    {
        $this->lazy = $lazy;

        return $this;
    }


    /**
     * Unmount the specified target
     *
     * @param Stringable|string $target
     * @param int|null          $timeout
     *
     * @return void
     */
    public function unmount(Stringable|string $target, ?int $timeout = null): void
    {
        if (Mount::isSource($target, false)) {
            // This is a mount source. Unmount all its targets
            $targets = PhoMounts::listMountTargets($target);
            foreach ($targets as $target) {
                static::unmount($target);
            }

        } else {
            if (!Mount::isMounted($target)) {
                throw new NotMountedException(tr('Cannot unmount target ":target", it is not mounted', [
                    ':target' => $target,
                ]));
            }
            Hook::new('phoundation')
                ->execute('file-system/unmount', [
                    'source'      => $source,
                    'target'      => $target,
                    'file-system' => $target,
                    'options'     => $target,
                    'timeout'     => $timeout,
                ]);
            // Build the process parameters, then execute
            try {
                $this->clearArguments()
                     ->setCommand('umount')
                     ->setSudo(true)
                     ->setTimeout($timeout)
                     ->appendArgument($this->force ? '-f' : null)
                     ->appendArgument($this->lazy ? '-l' : null)
                     ->appendArgument($target)
                     ->executeNoReturn();

            } catch (ProcessFailedException $e) {
                if (!$e->dataMatches('device is busy', key: 'output')) {
                    throw $e;
                }

                // The device is busy. Check by who and add it to the exception
                $processes = Lsof::new()->getForFile($target);

                throw UnmountBusyException::new(tr('Cannot unmount target ":target", it is busy', [
                    ':target' => $target,
                ]))->addData(['processes' => $processes]);
            }
        }
    }
}

<?php

/**
 * Class Packages
 *
 * This class tracks required packages per operating system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Packages;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Packages\Interfaces\PackagesInterface;
use Phoundation\Os\Processes\Commands\Command;
use Phoundation\Os\Processes\Exception\ProcessesException;
use Stringable;


class Packages extends Iterator implements PackagesInterface
{
    /**
     * Adds the package list for the specified operating system
     *
     * @param Stringable|string $operating_system
     * @param IteratorInterface|array|string $packages
     *
     * @return static
     */
    public function addForOperatingSystem(Stringable|string $operating_system, IteratorInterface|array|string $packages): static
    {
        if (!$operating_system) {
            throw new OutOfBoundsException(tr('Cannot add packages ":packages", no operating system specified', [
                ':packages' => $packages,
            ]));
        }
        if (!$packages or (($packages instanceof PackagesInterface) and $packages->isEmpty())) {
            throw new OutOfBoundsException(tr('Cannot add packages for operating system ":operating_system", no pacakges specified', [
                ':operating_system' => $operating_system,
            ]));
        }
        $os = $this->getValueOrDefault($operating_system, new Iterator());
        $os->addSources($packages);

        return $this;
    }


    /**
     * Installs the required packages for this operating system
     *
     * @param Stringable|string|null $operating_system
     *
     * @return static
     */
    public function install(Stringable|string|null $operating_system = null): static
    {
        throw new UnderConstructionException();
        $operating_system = $this->getOperatingSystem()
                                 ->default($operating_system);
        $manager          = $this->getManager($operating_system);
        if (!$this->keyExists($operating_system)) {
            throw new OutOfBoundsException(tr('Cannot install packages, operating system ":os" is not defined', [
                ':os' => $operating_system,
            ]));
        }
        if (
            $this->get($operating_system)
                 ->isEmpty()
        ) {
            throw new OutOfBoundsException(tr('Cannot install packages, no packages available for operating system ":os"', [
                ':os' => $operating_system,
            ]));
        }
        switch ($manager) {
            case 'apt':
                if (!Command::checkSudoAvailable('apt-get', FsRestrictions::new('/bin,/usr/bin,/sbin,/usr/sbin'))) {
                    throw new ProcessesException(tr('This process does not have sudo access to apt-get', [
                        ':command' => $command,
                    ]));
                }
                break;
            case 'yum':
                if (!Command::checkSudoAvailable('yum', FsRestrictions::new('/bin,/usr/bin,/sbin,/usr/sbin'))) {
                    throw new ProcessesException(tr('This process does not have sudo access to yum', [
                        ':command' => $command,
                    ]));
                }
                break;
            default:
                throw new OutOfBoundsException(tr('Unsupported package manager ":manager" detected', [
                    ':manager' => $manager,
                ]));
        }
        // TODO Implement this! Have apt-file actually search for the command, match /s?bin/COMMAND or /usr/s?bin/COMMAND
        //                    AptGet::new()->install($this->packages);
        //                    return $this->setInternalCommand($command, $which_command);
    }


    /**
     * Returns the package manager for the specified operating system
     *
     * @param string $operating_system
     *
     * @return string
     */
    public function getManager(string $operating_system): string
    {
        switch ($operating_system) {
            case '':
        }
    }
}

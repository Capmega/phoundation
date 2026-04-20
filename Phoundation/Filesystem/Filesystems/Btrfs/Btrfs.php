<?php

/**
 * Class Btrfs
 *
 * This is the core BTRFS filesystem management class.
 *
 * This class wraps the "btrfs" cli command and allows easy execution of processes.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Filesystems\Btrfs;

use Phoundation\Data\Traits\TraitDataObjectPath;
use Phoundation\Data\Traits\TraitDataObjectProcess;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Filesystems\Btrfs\Interfaces\BtrfsInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Strings;


class Btrfs implements BtrfsInterface
{
    use TraitDataObjectProcess;
    use TraitDataObjectPath {
        setPathObject as protected __setPathObject;
    }


    /**
     * BtrFs class constructor
     *
     * @param PhoPathInterface|null $_path
     */
    public function __construct(?PhoPathInterface $_path = null)
    {
        $this->setPathObject($_path);
    }


    /**
     * @param PhoPathInterface|null $_path
     *
     * @return static
     */
    public function setPathObject(?PhoPathInterface $_path): static
    {
        return $this->__setPathObject($_path)
                    ->setProcessObject(Process::new('btrfs', $_path?->getDirectoryObject())
                                              ->setSudo(true));
    }


    /**
     * Returns the version of the btrfs-progs
     *
     * @return string
     */
    public function getVersion(): string
    {
        $return = $this->getProcessObject()->clearArguments()->appendArgument('version');
        $return = Strings::fromReverse($return, ' v');

        return $return;
    }


    /**
     * Formats the current path with a BTRFS filesystem
     *
     * @return static
     */
    public function format(): static
    {
        Process::new('mkfs.btrfs')
               ->appendArguments(['-y', $this->getPathObject()->getSource()]);

        return $this;
    }


    /**
     * Returns the command line argument for what units to display
     *
     * @return string
     */
    protected function getUnitsArgument(): string
    {
        return match (UNITS) {
            'SI'    => '--si',
            'IEC'   => '--iec',
            default => throw new OutOfBoundsException(ts('Cannot generate units argument, system uses unknown or unsupported unit type ":unit"', [
                ':unit' => UNITS,
            ]))
        };
    }
}

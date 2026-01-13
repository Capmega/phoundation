<?php

/**
 * Class Btrfs
 *
 * This is the core BTRFS management class.
 *
 * This class wrapes the "btrfs" cli command and allows easy execution of processes.
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
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Os\Processes\Process;


class Btrfs
{
    use TraitDataObjectProcess;
    use TraitDataObjectPath {
        setPathObject as protected __setPathObject;
    }


    /**
     * BtrFs class constructor
     *
     * @param PhoPathInterface|null $o_path
     */
    public function __construct(?PhoPathInterface $o_path = null)
    {
        $this->setPathObject($o_path);
    }


    /**
     * @param PhoPathInterface|null $o_path
     *
     * @return static
     */
    public function setPathObject(?PhoPathInterface $o_path): static
    {
        return $this->__setPathObject($o_path)
                    ->setProcessObject(Process::new('btrfs', $o_path?->getDirectoryObject())
                                              ->setSudo(true));
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

<?php

declare(strict_types=1);

namespace Phoundation\Os\Packages\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Stringable;


/**
 * Interface PackagesInterface
 *
 * This class tracks required packages per operating system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
interface PackagesInterface
{
    /**
     * Adds the package list for the specified operating system
     *
     * @param Stringable|string              $operating_system
     * @param IteratorInterface|array|string $packages
     *
     * @return static
     */
    public function addForOperatingSystem(Stringable|string $operating_system, IteratorInterface|array|string $packages): static;

    /**
     * Installs the required packages for this operating system
     *
     * @param Stringable|string|null $operating_system
     *
     * @return $this
     */
    public function install(Stringable|string|null $operating_system = null): static;

    /**
     * Returns the package manager for the specified operating system
     *
     * @param string $operating_system
     *
     * @return string
     */
    public function getManager(string $operating_system): string;
}

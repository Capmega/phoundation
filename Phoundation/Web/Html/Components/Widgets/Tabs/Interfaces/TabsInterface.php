<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Tabs\Interfaces;

use Phoundation\Enums\Interfaces\EnumOrientationInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;


/**
 * Interface TabsInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
interface TabsInterface extends ElementsBlockInterface
{
    /**
     * Returns the orientation
     *
     * @return EnumOrientationInterface|null
     */
    public function getOrientation(): ?EnumOrientationInterface;

    /**
     * Sets the orientation
     *
     * @param EnumOrientationInterface|null $orientation
     *
     * @return static
     */
    public function setOrientation(?EnumOrientationInterface $orientation): static;
}
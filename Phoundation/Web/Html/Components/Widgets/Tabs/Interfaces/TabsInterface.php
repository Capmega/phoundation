<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Tabs\Interfaces;

use Phoundation\Enums\EnumOrientation;
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
     * @return EnumOrientation|null
     */
    public function getOrientation(): ?EnumOrientation;


    /**
     * Sets the orientation
     *
     * @param EnumOrientation|null $orientation
     *
     * @return static
     */
    public function setOrientation(?EnumOrientation $orientation): static;
}
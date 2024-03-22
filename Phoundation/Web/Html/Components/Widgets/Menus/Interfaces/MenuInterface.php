<?php

/**
 * Interface MenuInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Menus\Interfaces;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;

interface MenuInterface extends ElementsBlockInterface
{
    /**
     * Set the menu source and ensure all URL's are absolute
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     * @return $this
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static;
}
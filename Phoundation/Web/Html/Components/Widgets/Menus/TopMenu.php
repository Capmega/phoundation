<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Menus;

/**
 * TopMenu class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class TopMenu extends Menu
{
    /**
     * Default the top panel menu
     *
     * @return array
     */
    public function getSource(): array
    {
        if (!isset($this->source)) {
            $this->source = [
                tr('Front-end') => ['url' => '/'],
            ];
        }

        return $this->source;
    }
}
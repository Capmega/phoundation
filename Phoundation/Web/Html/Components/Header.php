<?php

/**
 * Class Header
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Exception\OutOfBoundsException;


class Header extends Div
{
    /**
     * Header class constructor
     *
     * @param string|null $content
     * @param int         $type
     */
    public function __construct(?string $content = null, int $type = 1)
    {
        if (($type < 1) or ($type > 6)) {
            throw new OutOfBoundsException(tr('Invalid header type ":type" specified, must be an integer number 1 - 6', [
                ':type' => $type
            ]));
        }

        parent::__construct($content);
        $this->setElement('h' . $type);
    }
}

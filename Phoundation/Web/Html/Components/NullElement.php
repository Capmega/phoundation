<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Web\Html\Components\Interfaces\NullElementInterface;


/**
 * NullElement class
 *
 * This is an empty element that will not render anything but its contents
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class NullElement extends Element implements NullElementInterface
{
    /**
     * NullElement class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setElement(null);
    }
}
<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Web\Html\Components\Interfaces\DivInterface;
use Phoundation\Web\Html\Traits\TraitChildElement;


/**
 * Div class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Div extends Element implements DivInterface
{
    use TraitChildElement;


    /**
     * Form class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->setElement('div');
    }
}
<?php

/**
 * Div class
 *
 * This class represents an HTML <div> element
 *
 * Any HTML element objects that are based off the <div> element should either extend this class, or the DivCore class
 *
 * @note: The core implementation of this class is done in DivCore, this class only contains the constructor and new
 *        methods
 *
 * @see \Phoundation\Web\Html\Components\DivCore
 * @see \Phoundation\Web\Html\Components\Element
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;


class Div extends DivCore
{
    /**
     * Div class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::___construct();

        $this->setElement('div')
             ->setContent($content);
    }
}

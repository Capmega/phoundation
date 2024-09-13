<?php

/**
 * Span class
 *
 * This class represents an HTML <span> element
 *
 * Any HTML element objects that are based off the <span> element should either extend this class, or the SpanCore class
 *
 * @note: The core implementation of this class is done in SpanCore, this class only contains the constructor and new
 *        methods
 *
 * @see \Phoundation\Web\Html\Components\SpanCore
 * @see \Phoundation\Web\Html\Components\Element
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Data\Traits\TraitStaticMethodNewWithContent;


class Span extends SpanCore
{
    use TraitStaticMethodNewWithContent;


    /**
     * Span class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::___construct();

        $this->setElement('span')
             ->setContent($content);
    }
}

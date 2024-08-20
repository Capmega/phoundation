<?php

/**
 * Class Element
 *
 * This class represents an HTML abstract HTML element.
 *
 * All HTML element objects must be based off this class, or the ElementCore class
 *
 * @note: The core implementation of this class is done in ElementCore, this class only contains the constructor and new
 *         methods
 *
 * @see \Phoundation\Web\Html\Components\ElementCore
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Data\Traits\TraitStaticMethodNewWithContent;


abstract class Element extends ElementCore
{
    use TraitStaticMethodNewWithContent;


    /**
     * Element class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::___construct();
        $this->setContent($content);
    }
}

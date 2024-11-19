<?php

/**
 * Class ResourceElement
 *
 * This class is an abstract HTML element object class that can display resource data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Data\Traits\TraitStaticMethodNewWithContent;


abstract class ResourceElement extends ResourceElementCore
{
    use TraitStaticMethodNewWithContent;


    /**
     * ResourceElement class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->empty = tr('No results available');
    }
}

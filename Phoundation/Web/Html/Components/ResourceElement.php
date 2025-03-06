<?php

/**
 * Class ResourceElement
 *
 * This class is an abstract HTML element object class that can display resource data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitStaticMethodNewIteratorArraySource;


abstract class ResourceElement extends ResourceElementCore
{
    use TraitStaticMethodNewIteratorArraySource;


    /**
     * ResourceElement class constructor
     *
     * @param IteratorInterface|array|null $source
     */
    public function __construct(IteratorInterface|array|null $source = null)
    {
        parent::__construct();

        $this->setSource($source)
             ->component_empty_label = tr('No results available');
    }
}

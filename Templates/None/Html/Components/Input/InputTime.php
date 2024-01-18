<?php

declare(strict_types=1);


namespace Templates\None\Html\Components\Input;

use Phoundation\Web\Html\Renderer;


/**
 * Class InputTime
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class InputTime extends Renderer
{
    /**
     * InputTime class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\Input\InputTime $element)
    {
        $element->addClass( 'form-control');
        parent::__construct($element);
    }
}
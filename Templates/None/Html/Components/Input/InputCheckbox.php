<?php

declare(strict_types=1);


namespace Templates\None\Html\Components\Input;

use Phoundation\Web\Http\Html\Renderer;


/**
 * Class InputCheckbox
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class InputCheckbox extends Renderer
{
    /**
     * InputCheckbox class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Input\InputCheckbox $element)
    {
        $element->addClass('form-check-input');
        parent::__construct($element);
    }
}
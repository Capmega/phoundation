<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components\Input;



/**
 * Class InputTime
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InputTime extends Input
{
    /**
     * InputTime class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Input\InputTime $element)
    {
        $element->addClass( 'form-control');
        parent::__construct($element);
    }
}
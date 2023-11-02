<?php

declare(strict_types=1);

namespace Templates\AdminLte\Html\Components\Input;


/**
 * Class InputHidden
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InputHidden extends Input
{
    /**
     * InputHidden class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Input\InputHidden $element)
    {
        $element->addClass( 'form-control');
        parent::__construct($element);
    }
}
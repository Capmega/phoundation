<?php

namespace Templates\AdminLte\Html\Components;



/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class FilterForm extends DataEntryForm
{
    /**
     * FilterForm class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\FilterForm $element)
    {
        parent::__construct($element);
    }
}
<?php

declare(strict_types=1);

namespace Phoundation\Business\Customers;

/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class FilterForm extends \Phoundation\Web\Http\Html\Components\FilterForm
{
    /**
     * FilterForm class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->fields = [
            'type[]'   => [
                'element' => 'inputmultibuttontext',
                'mode'    => 'info',
                'label'   => tr('Filters'),
                'source'  => [
                    '#name'   => tr('Name'),
                    '#code'   => tr('Code'),
                    '#email'  => tr('Email'),
                    '#phones' => tr('Phone number'),
                ],
            ],
        ];

        $this->keys_display = [
            'type[]'   => 12,
        ];
    }
}

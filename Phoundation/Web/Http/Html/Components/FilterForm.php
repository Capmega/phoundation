<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;


/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class FilterForm extends DataEntryForm
{
    /**
     * FilterForm class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->fields = [
            'type[]'   => [
                'label'    => tr('Type'),
                'element'  => 'select',
                'source'   => [
                ],
            ],
            'filter[]' => [
                'label'    => tr('Filter'),

            ],
        ];

        $this->keys_display = [
            'type[]'   => 6,
            'filter[]' => 6,
        ];
    }
}
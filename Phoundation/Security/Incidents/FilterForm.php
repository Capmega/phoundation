<?php

declare(strict_types=1);

namespace Phoundation\Security\Incidents;


/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 */
class FilterForm extends \Phoundation\Web\Http\Html\Components\FilterForm
{
    /**
     * FilterForm class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->definitions = [
            'type[]'   => [
                'label'    => tr('Type'),
                'element'  => 'select',
                'source'   => [
                    'type'     => tr('Type'),
                    'severity' => tr('Severity')
                ],
            ],
            'filter[]' => [
                'label'    => tr('Filter'),

            ],
        ];
    }
}
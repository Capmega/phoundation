<?php

declare(strict_types=1);

namespace Phoundation\Developer\Incidents;

/**
 * Class FilterForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */
class FilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    /**
     * FilterForm class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->definitions = [
            'type[]'   => [
                'label'   => tr('Type'),
                'element' => 'select',
                'source'  => [
                    'email'  => tr('Email'),
                    'phones' => tr('Phone number'),
                ],
            ],
            'filter[]' => [
                'label' => tr('Filter'),
            ],
        ];
    }
}
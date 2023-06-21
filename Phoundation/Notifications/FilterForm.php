<?php

declare(strict_types=1);

namespace Phoundation\Notifications;


/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
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
                    'all'    => tr('All'),
                    'unread' => tr('Unread'),
                    'read'   => tr('Read')
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
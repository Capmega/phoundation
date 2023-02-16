<?php

namespace Phoundation\Developer\Incidents;

use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryDetails;
use Phoundation\Data\DataEntry\Traits\DataEntryException;
use Phoundation\Data\DataEntry\Traits\DataEntryTitle;
use Phoundation\Data\DataEntry\Traits\DataEntryType;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;


/**
 * Incident class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Incident extends DataEntry
{
    use DataEntryDescription;
    use DataEntryDetails;
    use DataEntryException;
    use DataEntryTitle;
    use DataEntryType;
    use DataEntryUrl;



    /**
     * @inheritDoc
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'url' => [
                'disabled' => true,
                'label'    => tr('URL'),
            ],
            'title' => [
                'disabled' => true,
                'label'    => tr('Title'),
            ],
            'description' => [
                'disabled' => true,
                'element'  => 'text',
                'label'    => tr('Description'),
            ],
            'exception' => [
                'disabled' => true,
                'element'  => 'text',
                'label'    => tr('Exception'),
            ],
            'data' => [
                'disabled' => true,
                'element'  => 'text',
                'label'    => tr('Data'),
            ],
        ];

        $this->keys_display = [
            'title'       => 12,
            'description' => 12,
            'data'        => 12
        ] ;

        parent::setKeys();
    }
}

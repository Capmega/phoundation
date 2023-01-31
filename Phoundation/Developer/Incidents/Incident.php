<?php

namespace Phoundation\Developer\Incidents;

use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryData;
use Phoundation\Data\DataEntry\DataEntryDetails;
use Phoundation\Data\DataEntry\DataEntryException;
use Phoundation\Data\DataEntry\DataEntryTitleDescription;
use Phoundation\Data\DataEntry\DataEntryType;
use Phoundation\Data\DataEntry\DataEntryUrl;


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
    use DataEntryUrl;
    use DataEntryType;
    use DataEntryDetails;
    use DataEntryException;
    use DataEntryTitleDescription;



    /**
     * @inheritDoc
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'id' => [
                'disabled' => true,
                'type'     => 'numeric',
                'label'    => tr('Database ID')
            ],
            'created_on' => [
                'disabled'  => true,
                'type'      => 'text',
                'label'     => tr('Created on')
            ],
            'created_by' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Users::getHtmlSelect($key)
                        ->setSelected(isset_get($source['created_by']))
                        ->setDisabled(true)
                        ->render();
                },
                'label'    => tr('Created by')
            ],
            'meta_id' => [
                'disabled' => true,
                'element'  => null, //Meta::new()->getHtmlTable(), // TODO implement
                'label'    => tr('Meta information')
            ],
            'status' => [
                'disabled' => true,
                'default'  => tr('Ok'),
                'label'    => tr('Status')
            ],
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
            'id'          => 12,
            'created_by'  => 6,
            'created_on'  => 6,
            'meta_id'     => 6,
            'status'      => 6,
            'title'       => 12,
            'description' => 12,
            'data'        => 12
        ] ;
    }
}

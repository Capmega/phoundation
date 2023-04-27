<?php

declare(strict_types=1);

namespace Phoundation\Developer\Incidents;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinition;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryDetails;
use Phoundation\Data\DataEntry\Traits\DataEntryException;
use Phoundation\Data\DataEntry\Traits\DataEntryTitle;
use Phoundation\Data\DataEntry\Traits\DataEntryType;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;
use Phoundation\Data\Interfaces\InterfaceDataEntry;

/**
 * Incident class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
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
     * Plugin class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name  = 'incident';

        parent::__construct($identifier);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
        return [
            'type' => [
                'readonly' => true,
                'label'    => tr('URL'),
                'size'     => 6,
                'maxlength'=> 255,
            ],
            'title' => [
                'readonly' => true,
                'label'    => tr('Title'),
                'size'     => 6,
                'maxlength'=> 255,
            ],
            'url' => [
                'readonly' => true,
                'label'    => tr('URL'),
                'size'     => 12,
                'maxlength'=> 2048,
            ],
            'description' => [
                'readonly' => true,
                'element'  => 'text',
                'size'     => 12,
                'maxlength'=> 16_777_200,
                'label'    => tr('Description'),
            ],
            'exception' => [
                'readonly' => true,
                'element'  => 'text',
                'size'     => 12,
                'maxlength'=> 16_777_200,
                'label'    => tr('Exception'),
            ],
            'data' => [
                'readonly' => true,
                'element'  => 'text',
                'size'     => 12,
                'maxlength'=> 16_777_200,
                'label'    => tr('Data'),
            ],
        ];
    }
}

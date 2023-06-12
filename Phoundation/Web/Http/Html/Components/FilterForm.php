<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Data\DataEntry\DataEntryFieldDefinition;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Web\Http\Html\Enums\InputElement;


/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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

        $this->fields = DataEntryFieldDefinitions::new()
            ->add(DataEntryFieldDefinition::new('type[]')
                ->setLabel(tr('Type'))
                ->setSize(6)
                ->setElement(InputElement::select)
                ->setSource([]))
            ->add(DataEntryFieldDefinition::new('filter[]')
                ->setLabel(tr('Filter'))
                ->setSize(6));
    }
}
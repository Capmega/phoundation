<?php

declare(strict_types=1);

namespace Phoundation\Notifications;


use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Web\Html\Enums\EnumInputElement;

/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class FilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    /**
     * FilterForm class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->definitions = Definitions::new()
            ->add(Definition::new(null, 'type[]')
                ->setLabel(tr('Type'))
                ->setSize(6)
                ->setElement(EnumInputElement::select)
                ->setDataSource([]))
            ->add(Definition::new(null, 'filter[]')
                ->setLabel(tr('Filter'))
                ->setSize(6));
    }
}
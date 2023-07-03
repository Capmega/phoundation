<?php

declare(strict_types=1);

namespace Phoundation\Core\Plugins;


use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Web\Http\Html\Enums\InputElement;


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

        $this->definitions = Definitions::new()
            ->addDefinition(Definition::new($this, 'type[]')
                ->setLabel(tr('Type'))
                ->setSize(6)
                ->setElement(InputElement::select)
                ->setSource([
                    'all'      => tr('All'),
                    'enabled'  => tr('Enabled plugins'),
                    'disabled' => tr('Disabled plugins')
                ]))
            ->addDefinition(Definition::new($this, 'filter[]')
                ->setLabel(tr('Filter'))
                ->setSize(6));
    }
}
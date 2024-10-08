<?php

declare(strict_types=1);

namespace Phoundation\Core\Plugins;

use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Web\Html\Enums\EnumElement;

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
        $this->definitions = Definitions::new()
                                        ->add(Definition::new(null, 'type[]')
                                                        ->setLabel(tr('Type'))
                                                        ->setSize(6)
                                                        ->setElement(EnumElement::select)
                                                        ->setDataSource([
                                                            'all'      => tr('All'),
                                                            'enabled'  => tr('Enabled plugins'),
                                                            'disabled' => tr('Disabled plugins'),
                                                        ]))
                                        ->add(Definition::new(null, 'filter[]')
                                                        ->setLabel(tr('Filter'))
                                                        ->setSize(6));
    }
}
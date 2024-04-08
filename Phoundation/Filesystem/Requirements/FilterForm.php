<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Requirements;

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
 * @package   Phoundation\Filesystem
 */
class FilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    /**
     * The different status values to filter on
     *
     * @var array $states
     */
    protected array $states;


    /**
     * FilterForm class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->states = [
            '__all'   => tr('All'),
            null      => tr('Active'),
            'locked'  => tr('Locked'),
            'deleted' => tr('Deleted'),
        ];
        $this->definitions = Definitions::new()
                                        ->add(Definition::new(null, 'entry_status')
                                                        ->setLabel(tr('Status'))
                                                        ->setSize(4)
                                                        ->setOptional(true)
                                                        ->setElement(EnumElement::select)
                                                        ->setValue(isset_get($this->source['entry_status']))
                                                        ->setKey(true, 'auto_submit')
                                                        ->setDataSource($this->states));
    }
}

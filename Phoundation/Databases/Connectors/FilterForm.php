<?php

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


declare(strict_types=1);

namespace Phoundation\Databases\Connectors;

use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Web\Html\Enums\EnumElement;


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

        $connector = Connector::new();

        $this->definitions = Definitions::new()

                                        ->add(Definition::new(null, 'status')
                                                        ->setLabel(tr('Status'))
                                                        ->setSize(4)
                                                        ->setOptional(true)
                                                        ->setElement(EnumElement::select)
                                                        ->setValue(isset_get($this->source['status']))
                                                        ->setKey(true, 'auto_submit')
                                                        ->setDataSource($this->states))

                                        ->add($connector->getDefinitionsObject()
                                                        ->get('type'));

        // Auto apply
        $this->applyValidator(self::class);
    }
}
